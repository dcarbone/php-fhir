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
use DCarbone\PHPFHIR\Config\ProfilePackageConfig;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use DCarbone\PHPFHIR\Profiles\ProfileSet;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ProfileSetTest extends TestCase
{
    use PackageFixtures;

    private const _BASE = 'http://example.org/fhir/StructureDefinition/';

    private TemporaryDirectory $_tmpdir;
    private string $_root;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfileSetTest');
        $this->_tmpdir->create();
        $this->_root = rtrim($this->_tmpdir->path(''), '/');
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function child(string $id, string $parentId, string $type = 'Patient'): array
    {
        return self::structureDefinition($id, $type, 'constraint', ['baseDefinition' => self::_BASE . $parentId]);
    }

    private function package(array $sds, array $config = [], array $deps = [], string $name = 'test.ig'): \DCarbone\PHPFHIR\Profiles\ProfilePackage
    {
        $files = ['package.json' => self::manifest($name)];
        foreach ($sds as $sd) {
            $files['StructureDefinition-' . $sd['id'] . '.json'] = $sd;
        }
        $path = $this->_root . '/' . $name . '-' . uniqid();
        self::writePackageDir($path, $files);
        return ProfilePackageReader::read(ProfilePackageConfig::fromArray(array_merge(
            ['name' => $name, 'source' => $path],
            $config,
        )), null, $deps);
    }

    public function testBaseTypeProfilesHaveNoParent(): void
    {
        $set = ProfileSet::fromPackage($this->package([
            self::structureDefinition('a'),
            self::structureDefinition('b', 'Observation'),
        ]));
        $this->assertCount(2, $set->getProfiles());
        foreach ($set->getProfiles() as $p) {
            $this->assertNull($p->getParent());
        }
        $this->assertSame([], $set->getUnresolved());
    }

    public function testParentChainIsResolved(): void
    {
        $set = ProfileSet::fromPackage($this->package([
            $this->child('c', 'b'),
            $this->child('b', 'a'),
            self::structureDefinition('a'),
        ]));
        $c = $set->getProfileByUrl(self::_BASE . 'c');
        $this->assertSame('b', $c->getParent()->getId());
        $this->assertSame(['b', 'a'], array_map(fn($p) => $p->getId(), $c->getAncestors()));
        $this->assertTrue($c->hasParent());
    }

    public function testLookupByUrlWithVersionAndHas(): void
    {
        $set = ProfileSet::fromPackage($this->package([self::structureDefinition('a')]));
        $this->assertSame('a', $set->getProfileByUrl(self::_BASE . 'a|1.0.0')->getId());
        $this->assertTrue($set->hasProfile(self::_BASE . 'a'));
        $this->assertFalse($set->hasProfile(self::_BASE . 'nope'));
        $this->assertNull($set->getProfileByUrl(self::_BASE . 'nope'));
    }

    public function testEmptyPackage(): void
    {
        $set = ProfileSet::fromPackage($this->package([]));
        $this->assertSame([], $set->getProfiles());
    }
}
