<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Profiles;

/*
 * Copyright 2025-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DCarbone\PHPFHIR\Render\Templates;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the "meta.profile" resolution logic of the generated ProfileMap.
 */
class ProfileMapResolveTest extends TestCase
{
    private const _NS = 'Acme\\Map\\R4\\Profiles';
    private const _MAP = self::_NS . '\\ProfileMap';
    private const _PATIENT = 'Acme\\Map\\R4\\Types\\FHIRPatient';

    private const _A = 'http://ex.org/StructureDefinition/a';
    private const _B = 'http://ex.org/StructureDefinition/b';
    private const _C = 'http://ex.org/StructureDefinition/c';
    private const _OBS = 'http://ex.org/StructureDefinition/obs';

    public static function setUpBeforeClass(): void
    {
        if (class_exists(self::_MAP, false)) {
            return;
        }
        eval('namespace Acme\\Map\\R4\\Types; class FHIRPatient {} class FHIRObservation {}');
        // A is the root, B refines A, C is an unrelated sibling of A
        eval('namespace Acme\\Map\\R4\\Profiles;
            class A extends \\Acme\\Map\\R4\\Types\\FHIRPatient { public static function _getProfileVersion(): null|string { return "1.0.0"; } }
            class B extends A { public static function _getProfileVersion(): null|string { return "2.0.0"; } }
            class C extends \\Acme\\Map\\R4\\Types\\FHIRPatient { public static function _getProfileVersion(): null|string { return null; } }
            class Obs extends \\Acme\\Map\\R4\\Types\\FHIRObservation { public static function _getProfileVersion(): null|string { return null; } }');

        $classMap = [
            self::_A => self::_NS . '\\A',
            self::_B => self::_NS . '\\B',
            self::_C => self::_NS . '\\C',
            self::_OBS => self::_NS . '\\Obs',
        ];
        $baseTypeMap = [self::_A => 'Patient', self::_B => 'Patient', self::_C => 'Patient', self::_OBS => 'Observation'];
        $code = Templates::renderProfilePackageMapInterface('/* c */', self::_NS, 'PackageMapInterface');
        eval(substr($code, strlen('<?php')));
        $code = Templates::renderProfilePackageMap('/* c */', self::_NS . '\\Pkg', 'PackageMap', $classMap, $baseTypeMap, self::_NS . '\\PackageMapInterface');
        eval(substr($code, strlen('<?php')));
        $code = Templates::renderProfileMap('/* c */', self::_NS, 'ProfileMap', [self::_NS . '\\Pkg\\PackageMap'], CoreStubs::define());
        eval(substr($code, strlen('<?php')));
    }

    private static function resolve(array $declared,
                                    string $onUnknown = 'fallback',
                                    string $onAmbiguous = 'firstDeclared',
                                    string $onVersionMismatch = 'ignoreVersion'): null|string
    {
        $stubs = CoreStubs::define();
        $unknown = $stubs[CoreStubs::ON_UNKNOWN_PROFILE];
        $ambiguous = $stubs[CoreStubs::ON_AMBIGUOUS_PROFILE];
        $mismatch = $stubs[CoreStubs::ON_PROFILE_VERSION_MISMATCH];
        $map = self::_MAP;
        return $map::resolve(self::_PATIENT, $declared, $unknown::from($onUnknown), $ambiguous::from($onAmbiguous), $mismatch::from($onVersionMismatch));
    }

    private static function cls(string $short): string
    {
        return self::_NS . '\\' . $short;
    }

    public function testNoDeclaredProfilesResolvesToNull(): void
    {
        $this->assertNull(self::resolve([]));
        $this->assertNull(self::resolve(['', 5]));
    }

    public function testSingleKnownProfile(): void
    {
        $this->assertSame(self::cls('A'), self::resolve([self::_A]));
    }

    public function testUnknownProfileFallsBackOrThrows(): void
    {
        $this->assertNull(self::resolve(['http://nope']));
        $this->assertSame(self::cls('A'), self::resolve(['http://nope', self::_A]));

        $this->expectException(\UnexpectedValueException::class);
        self::resolve(['http://nope'], 'exception');
    }

    public function testProfileOfAnotherTypeIsUnknown(): void
    {
        $this->assertNull(self::resolve([self::_OBS]));

        $this->expectException(\UnexpectedValueException::class);
        self::resolve([self::_OBS], 'exception');
    }

    public function testVersionIgnoredByDefault(): void
    {
        $this->assertSame(self::cls('A'), self::resolve([self::_A . '|9.9.9']));
    }

    public function testVersionMismatchFallback(): void
    {
        $this->assertNull(self::resolve([self::_A . '|9.9.9'], 'fallback', 'exception', 'fallback'));
        $this->assertSame(self::cls('A'), self::resolve([self::_A . '|1.0.0'], 'fallback', 'exception', 'fallback'));
        // generated without a version, so nothing to compare against
        $this->assertSame(self::cls('C'), self::resolve([self::_C . '|9.9.9'], 'fallback', 'exception', 'fallback'));
    }

    public function testVersionMismatchException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        self::resolve([self::_A . '|9.9.9'], 'fallback', 'exception', 'exception');
    }

    public function testMostSpecificProfileWins(): void
    {
        $this->assertSame(self::cls('B'), self::resolve([self::_A, self::_B]));
        $this->assertSame(self::cls('B'), self::resolve([self::_B, self::_A]));
        $this->assertSame(self::cls('B'), self::resolve([self::_B, self::_A, self::_B]));
    }

    public function testAmbiguousProfiles(): void
    {
        $this->assertSame(self::cls('C'), self::resolve([self::_C, self::_B], 'fallback', 'firstDeclared'));
        $this->assertNull(self::resolve([self::_C, self::_B], 'fallback', 'fallback'));

        $this->expectException(\UnexpectedValueException::class);
        self::resolve([self::_C, self::_B], 'fallback', 'exception');
    }

    public function testLookups(): void
    {
        $map = self::_MAP;
        $this->assertSame(self::cls('B'), $map::getProfileClassname(self::_B . '|2.0.0'));
        $this->assertSame('Patient', $map::getProfileBaseType(self::_B));
        $this->assertNull($map::getProfileClassname('http://nope'));
    }
}
