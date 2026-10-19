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

    public function testRenderedClassImportsReferencedClasses(): void
    {
        $classes = $this->classes([
            self::structureDefinition('parent-p'),
            self::structureDefinition('child-p', 'Patient', 'constraint', ['baseDefinition' => self::_BASE . 'parent-p']),
        ]);
        $out = \DCarbone\PHPFHIR\Render\Templates::renderProfileClass('', $classes->getByUrl(self::_BASE . 'child-p'));
        $parentOut = \DCarbone\PHPFHIR\Render\Templates::renderProfileClass('', $classes->getByUrl(self::_BASE . 'parent-p'));
        $this->assertStringContainsString('use Acme\\Gen\\Versions\\R4\\Types\\FHIRPatient;', $parentOut);
        $this->assertStringContainsString('class ParentP extends FHIRPatient', $parentOut);
        $this->assertStringNotContainsString('use ' . self::_NS . '\\Ig\\ParentP;', $out); // same namespace
        $this->assertStringContainsString('class ChildP extends ParentP', $out);
        $this->assertStringNotContainsString('extends \\', $out);
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

        $stubs = CoreStubs::define();
        $profileInterface = $stubs[CoreStubs::PROFILE_TYPE_INTERFACE];
        $classes = ProfileClasses::build(
            $this->set([
                self::structureDefinition('parent-p', 'Patient', 'constraint', ['version' => '1.2.3']),
                self::structureDefinition('child-p', 'Patient', 'constraint', ['baseDefinition' => self::_BASE . 'parent-p']),
            ]),
            self::_NS,
            'Ig',
            self::resolver(),
            support: ['profileInterface' => $profileInterface],
        );

        $written = ProfileWriter::write($out, self::_NS, '/* copyright */', ['test.ig' => $classes], $stubs);
        $this->assertCount(8, $written);
        foreach ($written as $file) {
            $this->assertFileExists($file);
        }
        $this->assertFileExists($out . '/Acme/Gen/Versions/R4/Profiles/Ig/PackageAutoloader.php');
        $this->assertFileExists($out . '/Acme/Gen/Versions/R4/Profiles/Ig/ParentP.php');

        require $out . '/Acme/Gen/Versions/R4/Profiles/ProfileAutoloader.php';
        require $out . '/Acme/Gen/Versions/R4/Profiles/ProfileMap.php';
        $autoloader = self::_NS . '\\ProfileAutoloader';
        $packageAutoloader = self::_NS . '\\Ig\\PackageAutoloader';
        $this->assertFalse(class_exists($packageAutoloader, false));
        $this->assertTrue($autoloader::register());

        try {
            $childClass = self::_NS . '\\Ig\\ChildP';
            $child = new $childClass();
            $this->assertTrue(class_exists($packageAutoloader, false), 'the package autoloader is loaded on demand');
            $this->assertInstanceOf(self::_NS . '\\Ig\\ParentP', $child);
            $this->assertInstanceOf('Acme\\Gen\\Versions\\R4\\Types\\FHIRPatient', $child);
            $this->assertInstanceOf($profileInterface, $child);
            $this->assertSame(self::_BASE . 'child-p', $childClass::PROFILE_URL);
            $this->assertSame(self::_BASE . 'child-p', $childClass::_getProfileUrl());
            $this->assertNull($childClass::PROFILE_VERSION);
            $this->assertNull($childClass::_getProfileVersion());
            $parentClass = self::_NS . '\\Ig\\ParentP';
            $this->assertSame('1.2.3', $parentClass::PROFILE_VERSION);
            $this->assertSame('1.2.3', $parentClass::_getProfileVersion());
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

        // the package autoloader goes with it, and comes back on the next registration.
        $this->assertNotContains([$packageAutoloader, 'loadClass'], spl_autoload_functions());
        $this->assertTrue($autoloader::register());
        try {
            // the package's classes are all loaded already, so ask for one that does not exist.
            $this->assertFalse(class_exists(self::_NS . '\\Ig\\Missing'));
            $this->assertContains([$packageAutoloader, 'loadClass'], spl_autoload_functions());
        } finally {
            $autoloader::unregister();
        }
    }

    public function testClassesAreNestedLikeTheirBaseTypes(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);
        $ns = 'Acme\\Nest\\Versions\\R4\\Profiles';
        $types = 'Acme\\Nest\\Versions\\R4\\Types';
        eval('namespace ' . $types . '\\FHIRResource\\FHIRDomainResource; class FHIRPatient {}');

        $classes = ProfileClasses::build(
            $this->set([
                self::structureDefinition('parent-p'),
                self::structureDefinition('child-p', 'Patient', 'constraint', ['baseDefinition' => self::_BASE . 'parent-p']),
            ]),
            $ns,
            'Ig',
            static fn(string $t): string => $types . '\\FHIRResource\\FHIRDomainResource\\FHIR' . $t,
            null,
            [],
            null,
            [],
            $types,
        );
        $class = $classes->getByUrl(self::_BASE . 'child-p');
        $this->assertSame($ns . '\\Ig\\FHIRResource\\FHIRDomainResource', $class->getNamespace());
        $this->assertSame($ns . '\\Ig', $class->getPackageNamespace());

        ProfileWriter::write($out, $ns, '/* copyright */', ['test.ig' => $classes], CoreStubs::define());
        $base = $out . '/Acme/Nest/Versions/R4/Profiles';
        $this->assertFileExists($base . '/Ig/FHIRResource/FHIRDomainResource/ParentP.php');
        $this->assertFileExists($base . '/Ig/FHIRResource/FHIRDomainResource/ChildP.php');
        $this->assertFileExists($base . '/Ig/PackageMap.php');
        $this->assertFileExists($base . '/Ig/PackageAutoloader.php');
        $this->assertFileExists($base . '/ProfileMap.php');

        require $base . '/ProfileAutoloader.php';
        require $base . '/ProfileMap.php';
        $autoloader = $ns . '\\ProfileAutoloader';
        $autoloader::register();
        try {
            $childClass = $ns . '\\Ig\\FHIRResource\\FHIRDomainResource\\ChildP';
            $this->assertInstanceOf($ns . '\\Ig\\FHIRResource\\FHIRDomainResource\\ParentP', new $childClass());
            $map = $ns . '\\ProfileMap';
            $this->assertSame($childClass, $map::getProfileClassname(self::_BASE . 'child-p'));
        } finally {
            $autoloader::unregister();
        }
    }

    public function testNestedPackageNamespacesAreAllTried(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);
        $ns = 'Acme\\Overlap\\Versions\\R4\\Profiles';
        $types = 'Acme\\Overlap\\Versions\\R4\\Types';
        eval('namespace ' . $types . '; class FHIRPatient {}');
        $resolver = static fn(string $t): string => $types . '\\FHIR' . $t;

        // the general package is indexed first, so its namespace is the first to match classes of the specific one.
        $general = ProfileClasses::build($this->set([
            self::structureDefinition('g-1'),
            self::structureDefinition('g-2'),
        ]), $ns, 'Vendor', $resolver);
        $specific = ProfileClasses::build($this->set([
            self::structureDefinition('s-1'),
            self::structureDefinition('s-2'),
        ]), $ns, 'Vendor\\IG', $resolver);
        ProfileWriter::write($out, $ns, '/* copyright */', ['general' => $general, 'specific' => $specific], CoreStubs::define());

        require $out . '/Acme/Overlap/Versions/R4/Profiles/ProfileAutoloader.php';
        $autoloader = $ns . '\\ProfileAutoloader';
        $generalAutoloader = $ns . '\\Vendor\\PackageAutoloader';
        $specificAutoloader = $ns . '\\Vendor\\IG\\PackageAutoloader';

        // neither package registered yet.
        $autoloader::register();
        try {
            $this->assertTrue(class_exists($ns . '\\Vendor\\IG\\S1'));
            $this->assertContains([$specificAutoloader, 'loadClass'], spl_autoload_functions());
        } finally {
            $autoloader::unregister();
        }

        // the general package registered already.
        $autoloader::register();
        try {
            $this->assertTrue(class_exists($ns . '\\Vendor\\G1'));
            $this->assertContains([$generalAutoloader, 'loadClass'], spl_autoload_functions());
            $this->assertNotContains([$specificAutoloader, 'loadClass'], spl_autoload_functions());
            $this->assertTrue(class_exists($ns . '\\Vendor\\IG\\S2'));
            $this->assertContains([$specificAutoloader, 'loadClass'], spl_autoload_functions());
            $this->assertTrue(class_exists($ns . '\\Vendor\\G2'));
            $this->assertFalse(class_exists($ns . '\\Vendor\\IG\\Missing'));
        } finally {
            $autoloader::unregister();
        }
    }

    public function testWritePackageFilterOnlyWritesRequestedClassesButMapCoversAll(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);
        $classes = $this->classes([self::structureDefinition('a')]);
        $written = ProfileWriter::write($out, self::_NS, '/* c */', ['test.ig' => $classes], CoreStubs::define(), ['other']);
        $this->assertCount(6, $written);
        $this->assertFileDoesNotExist($out . '/Acme/Gen/Versions/R4/Profiles/Ig/A.php');
        $this->assertStringContainsString(self::_BASE . 'a', file_get_contents($out . '/Acme/Gen/Versions/R4/Profiles/Ig/PackageMap.php'));
    }

    public function testSameProfileInTwoPackagesFails(): void
    {
        $out = $this->_root . '/out';
        mkdir($out);
        $classes = $this->classes([self::structureDefinition('a')]);
        $this->expectException(ProfileDefinitionException::class);
        ProfileWriter::write($out, self::_NS, '/* c */', ['one' => $classes, 'two' => $classes], CoreStubs::define());
    }

    public function testProfileCopyrightFollowsLibraryCopyright(): void
    {
        $classes = $this->classes([
            self::structureDefinition('with', 'Patient', 'constraint', ['copyright' => "Used by permission.\nAll rights */ reserved."]),
            self::structureDefinition('without'),
        ]);
        $comment = "/*!\n * php-fhir\n *\n * FHIR Copyright Notice:\n *\n * HL7\n */";
        $with = \DCarbone\PHPFHIR\Render\Templates::renderProfileClass($comment, $classes->getByUrl(self::_BASE . 'with'));
        $this->assertStringContainsString(" * HL7\n *\n * Profile Copyright Notice:\n *\n * Package: test.ig 1.0.0\n *\n * Used by permission.\n * All rights * / reserved.\n */\n", $with);
        $without = \DCarbone\PHPFHIR\Render\Templates::renderProfileClass($comment, $classes->getByUrl(self::_BASE . 'without'));
        $this->assertStringNotContainsString('All rights', $without);
        $this->assertStringContainsString(" *\n * Profile Copyright Notice:\n *\n * Package: test.ig 1.0.0\n */\n", $without);
    }

    public function testPackageLicenseAndAuthorInHeader(): void
    {
        $path = $this->_root . '/pkg-lic';
        self::writePackageDir($path, [
            'package.json' => self::manifest('test.ig', ['license' => 'CC0-1.0', 'author' => 'HL7 International']),
            'StructureDefinition-a.json' => self::structureDefinition('a'),
        ]);
        $set = ProfileSet::fromPackage(ProfilePackageReader::read(ProfilePackageConfig::fromArray(['name' => 'test.ig', 'source' => $path])));
        $classes = ProfileClasses::build($set, self::_NS, 'Ig', self::resolver());
        $out = \DCarbone\PHPFHIR\Render\Templates::renderProfileClass("/*!\n * x\n */", $classes->getByUrl(self::_BASE . 'a'));
        $this->assertStringContainsString(" * Package: test.ig 1.0.0\n * Author: HL7 International\n * License: CC0-1.0\n */\n", $out);
    }
}
