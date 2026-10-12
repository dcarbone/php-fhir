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
            ['name' => $name, 'source' => $path, 'dependencies' => $deps],
            $config,
        )));
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

    public function testSelectedProfilesOrderedParentsFirst(): void
    {
        $set = ProfileSet::fromPackage($this->package([
            $this->child('c', 'b'),
            $this->child('b', 'a'),
            self::structureDefinition('a'),
            self::structureDefinition('z-other'),
        ]));
        $ids = array_map(fn($p) => $p->getId(), $set->getSelectedProfiles());
        $this->assertLessThan(array_search('b', $ids), array_search('a', $ids));
        $this->assertLessThan(array_search('c', $ids), array_search('b', $ids));
        $this->assertCount(4, $ids);
    }

    public function testSelectionFiltersButParentsStillResolve(): void
    {
        $set = ProfileSet::fromPackage($this->package(
            [$this->child('b', 'a'), self::structureDefinition('a')],
            ['exclude' => ['a']],
        ));
        $this->assertSame(['b'], array_map(fn($p) => $p->getId(), $set->getSelectedProfiles()));
        $this->assertCount(2, $set->getProfiles());
        $this->assertSame('a', $set->getProfileByUrl(self::_BASE . 'b')->getParent()->getId());
    }

    public function testUnresolvedParentThrowsWhenStrict(): void
    {
        $package = $this->package([$this->child('b', 'missing')]);
        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage('dependencies');
        ProfileSet::fromPackage($package, true);
    }

    public function testUnresolvedParentRecordedWhenNotStrict(): void
    {
        $set = ProfileSet::fromPackage($this->package([$this->child('b', 'missing')]), false);
        $this->assertNull($set->getProfileByUrl(self::_BASE . 'b')->getParent());
        $this->assertSame(['b' => self::_BASE . 'missing'], $set->getUnresolved());
    }

    public function testCircularChainThrows(): void
    {
        $package = $this->package([$this->child('a', 'b'), $this->child('b', 'a')]);
        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage('circular');
        ProfileSet::fromPackage($package);
    }

    public function testSelfReferenceThrows(): void
    {
        $package = $this->package([$this->child('a', 'a')]);
        $this->expectException(ProfileDefinitionException::class);
        ProfileSet::fromPackage($package);
    }

    public function testParentResolvesThroughDependency(): void
    {
        $depPath = $this->_root . '/dep';
        self::writePackageDir($depPath, [
            'package.json' => self::manifest('dep.ig'),
            'StructureDefinition-dep-base.json' => self::structureDefinition('dep-base'),
        ]);
        $package = $this->package([$this->child('mine', 'dep-base')], [], ['dep.ig' => $depPath]);

        $set = ProfileSet::fromPackage($package);
        $mine = $set->getProfileByUrl(self::_BASE . 'mine');
        $this->assertSame('dep-base', $mine->getParent()->getId());
        // dependency profiles are resolvable but are not part of this package's own set.
        $this->assertSame(['mine'], array_map(fn($p) => $p->getId(), $set->getProfiles()));
        $this->assertSame(['mine'], array_map(fn($p) => $p->getId(), $set->getSelectedProfiles()));
        $this->assertTrue($set->hasProfile(self::_BASE . 'dep-base'));
    }

    public function testOwnProfileWinsUrlCollisionWithDependency(): void
    {
        $depPath = $this->_root . '/dep';
        self::writePackageDir($depPath, [
            'package.json' => self::manifest('dep.ig'),
            'StructureDefinition-same.json' => self::structureDefinition('same', 'Patient', 'constraint', ['title' => 'dep']),
        ]);
        $package = $this->package(
            [self::structureDefinition('same', 'Patient', 'constraint', ['title' => 'own'])],
            [],
            ['dep.ig' => $depPath],
        );
        $this->assertSame('own', ProfileSet::fromPackage($package)->getProfileByUrl(self::_BASE . 'same')->getTitle());
    }

    public function testMalformedProfileThrows(): void
    {
        $bad = self::structureDefinition('bad');
        unset($bad['type']);
        $package = $this->package([$bad]);
        $this->expectException(ProfileDefinitionException::class);
        ProfileSet::fromPackage($package);
    }

    public function testEmptyPackage(): void
    {
        $set = ProfileSet::fromPackage($this->package([]));
        $this->assertSame([], $set->getProfiles());
        $this->assertSame([], $set->getSelectedProfiles());
    }
}
