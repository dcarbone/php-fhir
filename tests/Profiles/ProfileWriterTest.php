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
use DCarbone\PHPFHIR\Profiles\ProfileClass;
use DCarbone\PHPFHIR\Profiles\ProfileClasses;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use DCarbone\PHPFHIR\Profiles\ProfileSet;
use DCarbone\PHPFHIR\Profiles\ProfileWriter;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ProfileWriterTest extends TestCase
{
    use PackageFixtures;

    private const _BASE = 'http://example.org/fhir/StructureDefinition/';
    private const _NS = 'Acme\\Gen\\Versions\\R4\\Profiles';

    private TemporaryDirectory $_tmpdir;
    private string $_root;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfileWriterTest');
        $this->_tmpdir->create();
        $this->_root = rtrim($this->_tmpdir->path(''), '/');
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function set(array $sds, array $config = []): ProfileSet
    {
        $files = ['package.json' => self::manifest('test.ig')];
        foreach ($sds as $sd) {
            $files['StructureDefinition-' . $sd['id'] . '.json'] = $sd;
        }
        $path = $this->_root . '/pkg-' . uniqid();
        self::writePackageDir($path, $files);
        return ProfileSet::fromPackage(ProfilePackageReader::read(ProfilePackageConfig::fromArray(
            array_merge(['name' => 'test.ig', 'source' => $path], $config),
        )));
    }

    private static function resolver(): \Closure
    {
        return static fn(string $t): null|string => 'Acme\\Gen\\Versions\\R4\\Types\\FHIR' . $t;
    }

    private function classes(array $sds, array $config = []): ProfileClasses
    {
        return ProfileClasses::build($this->set($sds, $config), self::_NS, 'Ig', self::resolver());
    }

    public function testSanitizeClassName(): void
    {
        $this->assertSame('USCorePatient', ProfileClass::sanitizeClassName('USCorePatient'));
        $this->assertSame('MyPatientProfile', ProfileClass::sanitizeClassName('my-patient_profile'));
        $this->assertSame('Profile3dThing', ProfileClass::sanitizeClassName('3d thing'));
        $this->expectException(ProfileDefinitionException::class);
        ProfileClass::sanitizeClassName('---');
    }

    public function testBuildExtendsBaseTypeAndParent(): void
    {
        $classes = $this->classes([
            self::structureDefinition('parent-p'),
            self::structureDefinition('child-p', 'Patient', 'constraint', ['baseDefinition' => self::_BASE . 'parent-p']),
        ]);
        $this->assertCount(2, $classes);
        $parent = $classes->getByUrl(self::_BASE . 'parent-p');
        $child = $classes->getByUrl(self::_BASE . 'child-p');
        $this->assertSame('\\Acme\\Gen\\Versions\\R4\\Types\\FHIRPatient', $parent->getExtendsClassName());
        $this->assertSame($parent, $child->getParent());
        $this->assertSame('\\' . self::_NS . '\\Ig\\ParentP', $child->getExtendsClassName());
        $this->assertSame(self::_NS . '\\Ig\\ChildP', $child->getFullyQualifiedClassName(false));
    }

    public function testSharedNamesFallBackToIds(): void
    {
        $classes = $this->classes([
            self::structureDefinition('a-one', 'Patient', 'constraint', ['name' => 'Same']),
            self::structureDefinition('b-one', 'Patient', 'constraint', ['name' => 'same']),
            self::structureDefinition('c-one', 'Patient', 'constraint', ['name' => 'Unique']),
        ]);
        $names = [];
        foreach ($classes as $class) {
            $names[] = $class->getClassName();
        }
        sort($names);
        $this->assertSame(['AOne', 'BOne', 'Unique'], $names);
    }

    public function testSharedNamesAndIdsStillCollideFails(): void
    {
        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage('both produce class name');
        $this->classes([
            self::structureDefinition('a-one', 'Patient', 'constraint', ['name' => 'Same']),
            self::structureDefinition('a_one', 'Patient', 'constraint', ['name' => 'same']),
        ]);
    }

    public function testUnknownBaseTypeFails(): void
    {
        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage('was not generated');
        ProfileClasses::build($this->set([self::structureDefinition('a')]), self::_NS, 'Ig', static fn() => null);
    }

    public function testWriteGeneratesLoadableClasses(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);

        // stand-in for the generated base type.
        eval('namespace Acme\\Gen\\Versions\\R4\\Types; class FHIRPatient {}');

        $classes = $this->classes([
            self::structureDefinition('parent-p', 'Patient', 'constraint', ['version' => '1.2.3']),
            self::structureDefinition('child-p', 'Patient', 'constraint', ['baseDefinition' => self::_BASE . 'parent-p']),
        ]);

        $written = ProfileWriter::write($out, self::_NS, '/* copyright */', ['test.ig' => $classes]);
        $this->assertCount(6, $written);
        foreach ($written as $file) {
            $this->assertFileExists($file);
        }
        $this->assertFileExists($out . '/Acme/Gen/Versions/R4/Profiles/Ig/ParentP.php');

        require $out . '/Acme/Gen/Versions/R4/Profiles/ProfileAutoloader.php';
        require $out . '/Acme/Gen/Versions/R4/Profiles/ProfileMap.php';
        $autoloader = self::_NS . '\\ProfileAutoloader';
        $this->assertTrue($autoloader::register());

        try {
            $childClass = self::_NS . '\\Ig\\ChildP';
            $child = new $childClass();
            $this->assertInstanceOf(self::_NS . '\\Ig\\ParentP', $child);
            $this->assertInstanceOf('Acme\\Gen\\Versions\\R4\\Types\\FHIRPatient', $child);
            $this->assertSame(self::_BASE . 'child-p', $childClass::PROFILE_URL);
            $this->assertSame(self::_BASE . 'child-p', $childClass::_getProfileUrl());
            $this->assertNull($childClass::PROFILE_VERSION);
            $parentClass = self::_NS . '\\Ig\\ParentP';
            $this->assertSame('1.2.3', $parentClass::PROFILE_VERSION);
            $this->assertSame('Patient', $parentClass::PROFILE_BASE_TYPE);

            $map = self::_NS . '\\ProfileMap';
            $this->assertSame($childClass, $map::getProfileClassname(self::_BASE . 'child-p'));
            $this->assertSame($childClass, $map::getProfileClassname(self::_BASE . 'child-p|9.9'));
            $this->assertNull($map::getProfileClassname('http://nope'));
            $this->assertSame('Patient', $map::getProfileBaseType(self::_BASE . 'child-p|1'));
            $this->assertNull($map::getProfileBaseType('http://nope'));
            $this->assertCount(2, $map::getProfileClasses());
        } finally {
            $autoloader::unregister();
        }
    }

    public function testWritePackageFilterOnlyWritesRequestedClassesButMapCoversAll(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);
        $classes = $this->classes([self::structureDefinition('a')]);
        $written = ProfileWriter::write($out, self::_NS, '/* c */', ['test.ig' => $classes], ['other']);
        $this->assertCount(4, $written);
        $this->assertFileDoesNotExist($out . '/Acme/Gen/Versions/R4/Profiles/Ig/A.php');
        $this->assertStringContainsString(self::_BASE . 'a', file_get_contents($out . '/Acme/Gen/Versions/R4/Profiles/ProfileMap.php'));
    }

    public function testSameProfileInTwoPackagesFails(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);
        $classes = $this->classes([self::structureDefinition('a')]);
        $this->expectException(ProfileDefinitionException::class);
        ProfileWriter::write($out, self::_NS, '/* c */', ['one' => $classes, 'two' => $classes]);
    }
}
