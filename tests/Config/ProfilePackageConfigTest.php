<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Config;

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

use DCarbone\PHPFHIR\Config\ProfilePackageConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ProfilePackageConfigTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfilePackageConfigTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function sourceDir(string $name = 'pkg'): string
    {
        $path = $this->_tmpdir->path($name);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    public static function deriveNamespaceProvider(): array
    {
        return [
            'us core' => ['hl7.fhir.us.core', 'UsCore'],
            'extensions' => ['hl7.fhir.uv.extensions.r4', 'UvExtensionsR4'],
            'no hl7 prefix' => ['us.cdc.phinvads', 'UsCdcPhinvads'],
            'dashes' => ['my-custom_ig', 'MyCustomIg'],
        ];
    }

    #[DataProvider('deriveNamespaceProvider')]
    public function testDeriveNamespace(string $name, string $expected): void
    {
        $this->assertSame($expected, ProfilePackageConfig::deriveNamespace($name));
    }

    public function testConstructDefaults(): void
    {
        $src = $this->sourceDir();
        $c = new ProfilePackageConfig(name: 'hl7.fhir.us.core', source: $src);
        $this->assertSame('hl7.fhir.us.core', $c->getName());
        $this->assertSame($src, $c->getSource());
        $this->assertFalse($c->isSourceArchive());
        $this->assertSame('UsCore', $c->getNamespace());
        $this->assertSame([], $c->getInclude());
        $this->assertSame([], $c->getExclude());
        $this->assertSame([], $c->getDependencies());
        $this->assertTrue($c->isStrict());
    }

    public function testSourceFileIsArchive(): void
    {
        $file = $this->_tmpdir->path('pkg.tgz');
        file_put_contents($file, 'x');
        $c = new ProfilePackageConfig(name: 'a.b', source: $file);
        $this->assertTrue($c->isSourceArchive());
    }

    public function testFromArrayAllKeys(): void
    {
        $src = $this->sourceDir();
        $dep = $this->sourceDir('dep');
        $c = ProfilePackageConfig::fromArray([
            'name' => 'hl7.fhir.us.core',
            'source' => $src,
            'namespace' => '\\Custom\\Ns\\',
            'include' => ['us-core-*'],
            'exclude' => ['*-adi-*'],
            'dependencies' => ['hl7.fhir.r4.core' => $dep],
            'strict' => false,
        ]);
        $this->assertSame('Custom\\Ns', $c->getNamespace());
        $this->assertSame(['us-core-*'], $c->getInclude());
        $this->assertSame(['*-adi-*'], $c->getExclude());
        $this->assertSame(['hl7.fhir.r4.core' => $dep], $c->getDependencies());
        $this->assertFalse($c->isStrict());
    }

    public function testFromArrayRequiresName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProfilePackageConfig::fromArray(['source' => $this->sourceDir()]);
    }

    public function testFromArrayRequiresSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProfilePackageConfig::fromArray(['name' => 'a.b']);
    }

    public function testFromArrayRejectsUnknownKey(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        ProfilePackageConfig::fromArray(['name' => 'a.b', 'source' => $this->sourceDir(), 'nope' => 1]);
    }

    public function testFromArrayRejectsNonBoolStrict(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProfilePackageConfig::fromArray(['name' => 'a.b', 'source' => $this->sourceDir(), 'strict' => 'yes']);
    }

    public static function invalidNameProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'uppercase' => ['HL7.fhir'],
            'space' => ['a b'],
            'leading dot' => ['.abc'],
            'slash' => ['a/b'],
        ];
    }

    #[DataProvider('invalidNameProvider')]
    public function testInvalidNameRejected(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: $name, source: $this->sourceDir());
    }

    public function testMissingSourceRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: 'a.b', source: $this->_tmpdir->path('') . '/does-not-exist');
    }

    public function testEmptySourceRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: 'a.b', source: '');
    }

    public function testInvalidDerivedNamespaceRejected(): void
    {
        // derives to "1abc", which is not a valid namespace
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: '1abc', source: $this->sourceDir());
    }

    public function testInvalidExplicitNamespaceRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir(), namespace: 'not valid');
    }

    public function testExplicitNamespaceRescuesInvalidDerived(): void
    {
        $c = new ProfilePackageConfig(name: '1abc', source: $this->sourceDir(), namespace: 'Abc');
        $this->assertSame('Abc', $c->getNamespace());
    }

    public function testEmptyPatternRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir(), include: ['ok', ' ']);
    }

    public function testNonStringPatternRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir(), exclude: [1]);
    }

    public function testDependencyWithMissingPathRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(
            name: 'a.b',
            source: $this->sourceDir(),
            dependencies: ['x.y' => $this->_tmpdir->path('') . '/nope'],
        );
    }

    public function testDependencyListRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir(), dependencies: [$this->sourceDir('d')]);
    }

    public function testIsProfileSelectedNoFilters(): void
    {
        $c = new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir());
        $this->assertTrue($c->isProfileSelected('anything'));
    }

    public function testIsProfileSelectedInclude(): void
    {
        $c = new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir(), include: ['us-core-*']);
        $this->assertTrue($c->isProfileSelected('us-core-patient'));
        $this->assertFalse($c->isProfileSelected('other-patient'));
    }

    public function testIsProfileSelectedExcludeWins(): void
    {
        $c = new ProfilePackageConfig(
            name: 'a.b',
            source: $this->sourceDir(),
            include: ['us-core-*'],
            exclude: ['*-adi-*'],
        );
        $this->assertTrue($c->isProfileSelected('us-core-patient'));
        $this->assertFalse($c->isProfileSelected('us-core-adi-documentreference'));
    }

    public function testIsProfileSelectedMatchesAnyCandidate(): void
    {
        $c = new ProfilePackageConfig(name: 'a.b', source: $this->sourceDir(), include: ['USCorePatient']);
        $this->assertTrue($c->isProfileSelected('us-core-patient', 'USCorePatient'));
        $this->assertFalse($c->isProfileSelected('us-core-patient'));
    }
}
