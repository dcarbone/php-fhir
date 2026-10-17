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
use DCarbone\PHPFHIR\Profiles\ProfilePackage;
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

    private function package(array $sds, array $config = [], array $deps = [], string $name = 'test.ig', array $manifest = []): ProfilePackage
    {
        $files = ['package.json' => self::manifest($name, $manifest)];
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
        $ids = [];
        foreach ($c->getAncestors() as $ancestor) {
            $ids[] = $ancestor->getId();
        }
        $this->assertSame(['b', 'a'], $ids);
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

    public function testUnresolvedBaseIsRecordedWhenNotStrict(): void
    {
        $set = ProfileSet::fromPackage($this->package([$this->child('a', 'missing')]), false);
        $this->assertNull($set->getProfileByUrl(self::_BASE . 'a')->getParent());
        $this->assertSame(['a' => self::_BASE . 'missing'], $set->getUnresolved());
    }

    public function testMissingBaseHintsAtUnconfiguredDependencies(): void
    {
        $package = $this->package([$this->child('a', 'missing')], manifest: ['dependencies' => ['hl7.fhir.r4.core' => '4.0.1', 'other.ig' => '2.0.0']]);
        try {
            ProfileSet::fromPackage($package);
            $this->fail('expected an exception');
        } catch (ProfileDefinitionException $e) {
            $this->assertStringContainsString('not configured: other.ig (2.0.0).', $e->getMessage());
        }
    }

    public function testMissingCoreProfileBaseHintsAtCorePackage(): void
    {
        $sd = self::structureDefinition('a', 'Observation', 'constraint', ['baseDefinition' => 'http://hl7.org/fhir/StructureDefinition/vitalsigns']);
        $package = $this->package([$sd], manifest: ['dependencies' => ['hl7.fhir.r4.core' => '4.0.1']]);
        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage('not configured: hl7.fhir.r4.core (4.0.1).');
        ProfileSet::fromPackage($package);
    }

    public function testDependencyProfilesAreSharedOnceAndNotRechecked(): void
    {
        // shared is reached through both one and two.  Its unresolvable base was reported when it was built.
        $shared = $this->package([$this->child('shared', 'missing')], ['strict' => false], [], 'shared.ig');
        $one = $this->package([$this->child('one', 'shared')], [], [$shared], 'one.ig');
        $two = $this->package([$this->child('two', 'shared')], [], [$shared], 'two.ig');
        $mine = $this->package([$this->child('mine', 'one'), $this->child('mine2', 'two')], [], [$one, $two], 'mine.ig');

        $set = ProfileSet::fromPackage($mine);
        $this->assertSame([], $set->getUnresolved());
        $this->assertSame('shared', $set->getProfileByUrl(self::_BASE . 'mine')->getParent()->getParent()->getId());
        $this->assertSame(
            $set->getProfileByUrl(self::_BASE . 'mine')->getParent()->getParent(),
            $set->getProfileByUrl(self::_BASE . 'mine2')->getParent()->getParent(),
        );
        $this->assertCount(2, $set->getProfiles());
    }
}
