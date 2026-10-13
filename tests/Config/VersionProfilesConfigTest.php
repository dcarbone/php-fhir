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
use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Config\VersionProfilesConfig;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class VersionProfilesConfigTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'VersionProfilesConfigTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function dir(string $name): string
    {
        $path = $this->_tmpdir->path($name);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    public function testDefaultsAreEmpty(): void
    {
        $c = new VersionProfilesConfig();
        $this->assertSame('Profiles', $c->getNamespace());
        $this->assertFalse($c->hasPackages());
        $this->assertSame([], $c->getPackages());
        $this->assertSame([], $c->getPackageNames());
    }

    public function testCustomNamespace(): void
    {
        $c = new VersionProfilesConfig(namespace: '\\MyProfiles\\');
        $this->assertSame('MyProfiles', $c->getNamespace());
    }

    public function testInvalidNamespaceRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new VersionProfilesConfig(namespace: 'not valid');
    }

    public function testFromArrayEmpty(): void
    {
        $c = VersionProfilesConfig::fromArray([]);
        $this->assertFalse($c->hasPackages());
    }

    public function testFromArrayWithArrayAndInstancePackages(): void
    {
        $c = VersionProfilesConfig::fromArray([
            'namespace' => 'Pr',
            'packages' => [
                ['name' => 'hl7.fhir.us.core', 'source' => $this->dir('core')],
                new ProfilePackageConfig(name: 'my.ig', source: $this->dir('ig')),
            ],
        ]);
        $this->assertSame('Pr', $c->getNamespace());
        $this->assertTrue($c->hasPackages());
        $this->assertSame(['hl7.fhir.us.core', 'my.ig'], $c->getPackageNames());
        $this->assertTrue($c->hasPackage('my.ig'));
        $this->assertFalse($c->hasPackage('nope'));
        $this->assertSame('UsCore', $c->getPackage('hl7.fhir.us.core')->getNamespace());
        $this->assertCount(2, $c->getPackages());
    }

    public function testFromArrayRejectsUnknownKey(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        VersionProfilesConfig::fromArray(['bogus' => true]);
    }

    public function testFromArrayRejectsNonIterablePackages(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        VersionProfilesConfig::fromArray(['packages' => 'nope']);
    }

    public function testDuplicatePackageNameRejected(): void
    {
        $c = new VersionProfilesConfig();
        $c->addPackage(['name' => 'a.b', 'source' => $this->dir('one')]);
        $this->expectException(\InvalidArgumentException::class);
        $c->addPackage(['name' => 'a.b', 'source' => $this->dir('two'), 'namespace' => 'Other']);
    }

    public function testConflictingNamespaceRejected(): void
    {
        $c = new VersionProfilesConfig();
        $c->addPackage(['name' => 'a.b', 'source' => $this->dir('one'), 'namespace' => 'Same']);
        $this->expectException(\DomainException::class);
        $c->addPackage(['name' => 'c.d', 'source' => $this->dir('two'), 'namespace' => 'same']);
    }

    private function pkg(string $name, array $deps = []): array
    {
        return ['name' => $name, 'source' => $this->dir($name), 'namespace' => 'N' . str_replace('.', '', $name), 'dependencies' => $deps];
    }

    public function testPackagesAreOrderedDependenciesFirst(): void
    {
        $c = new VersionProfilesConfig(packages: [
            $this->pkg('c.ig', ['b.ig']),
            $this->pkg('b.ig', ['a.ig']),
            $this->pkg('a.ig'),
        ]);
        $this->assertSame(['a.ig', 'b.ig', 'c.ig'], array_map(fn($p) => $p->getName(), $c->getOrderedPackages()));
        $this->assertEqualsCanonicalizing(['a.ig', 'b.ig'], $c->getDependencyClosure('c.ig'));
        $this->assertSame([], $c->getDependencyClosure('a.ig'));
    }

    public function testUnconfiguredDependencyRejected(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not configured');
        new VersionProfilesConfig(packages: [$this->pkg('a.ig', ['missing.ig'])]);
    }

    public function testCircularDependencyRejected(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('circular');
        new VersionProfilesConfig(packages: [$this->pkg('a.ig', ['b.ig']), $this->pkg('b.ig', ['a.ig'])]);
    }

    public function testGetUnknownPackageThrows(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        (new VersionProfilesConfig())->getPackage('nope');
    }

    public function testVersionConfigWithoutProfiles(): void
    {
        $vc = VersionConfig::fromArray(['name' => 'R4', 'schemaPath' => $this->dir('schema')]);
        $this->assertFalse($vc->getProfilesConfig()->hasPackages());
        $this->assertSame('Profiles', $vc->getProfilesConfig()->getNamespace());
    }

    public function testVersionConfigWithProfilesArray(): void
    {
        $vc = VersionConfig::fromArray([
            'name' => 'R4',
            'schemaPath' => $this->dir('schema'),
            'profiles' => [
                'packages' => [['name' => 'hl7.fhir.us.core', 'source' => $this->dir('core')]],
            ],
        ]);
        $this->assertTrue($vc->getProfilesConfig()->hasPackage('hl7.fhir.us.core'));
    }

    public function testVersionConfigWithProfilesInstance(): void
    {
        $pc = new VersionProfilesConfig(namespace: 'Xx');
        $vc = new VersionConfig(name: 'R4', schemaPath: $this->dir('schema'), profilesConfig: $pc);
        $this->assertSame($pc, $vc->getProfilesConfig());
    }

    public function testConfigAcceptsProfilesPerVersion(): void
    {
        $config = \DCarbone\PHPFHIR\Config::fromArray([
            'libraryPath' => $this->dir('out'),
            'versions' => [[
                'name' => 'R4',
                'schemaPath' => $this->dir('schema'),
                'profiles' => ['packages' => [['name' => 'a.b', 'source' => $this->dir('pkg')]]],
            ]],
        ]);
        $this->assertTrue($config->getVersion('R4')->getVersionConfig()->getProfilesConfig()->hasPackage('a.b'));
    }
}
