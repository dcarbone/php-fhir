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
use DCarbone\PHPFHIR\Profiles\ProfilePackage;
use DCarbone\PHPFHIR\Profiles\ProfilePackageException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ProfilePackageReaderTest extends TestCase
{
    use PackageFixtures;

    private TemporaryDirectory $_tmpdir;
    private string $_root;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfilePackageReaderTest');
        $this->_tmpdir->create();
        $this->_root = rtrim($this->_tmpdir->path(''), '/');
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function standardFiles(): array
    {
        return [
            'package.json' => self::manifest(),
            '.index.json' => ['index-version' => 1, 'files' => []],
            'StructureDefinition-my-patient.json' => self::structureDefinition('my-patient'),
            'StructureDefinition-my-observation.json' => self::structureDefinition('my-observation', 'Observation'),
            'StructureDefinition-base-thing.json' => self::structureDefinition('base-thing', 'Thing', 'specialization'),
            'ValueSet-my-vs.json' => self::valueSet('my-vs'),
            'CodeSystem-my-cs.json' => self::codeSystem('my-cs'),
            'SearchParameter-ignored.json' => ['resourceType' => 'SearchParameter', 'id' => 'ignored'],
            'notes.txt' => 'not json, and not read',
        ];
    }

    private function config(string $source, array $extra = []): ProfilePackageConfig
    {
        return ProfilePackageConfig::fromArray(array_merge(['name' => 'test.ig', 'source' => $source], $extra));
    }

    /**
     * @return array<string, array{0: string}>
     */
    private function sourceVariants(): array
    {
        return [
            'directory' => self::writePackageDir($this->_root . '/dir', $this->standardFiles()),
            'directory without package subdir' => self::writePackageDir($this->_root . '/flat', $this->standardFiles(), false),
            'archive' => self::writePackageArchive($this->_root . '/pkg.tgz', $this->standardFiles()),
        ];
    }

    public function testReadsAllSourceVariants(): void
    {
        foreach ($this->sourceVariants() as $label => $source) {
            $package = ProfilePackageReader::read($this->config($source), '4.0.1');
            $this->assertSame('test.ig', $package->getName(), $label);
            $this->assertSame('1.0.0', $package->getVersion(), $label);
            $this->assertSame('http://example.org/fhir/test', $package->getCanonical(), $label);
            $this->assertSame(['4.0.1'], $package->getFHIRVersions(), $label);
            $this->assertSame(['hl7.fhir.r4.core' => '4.0.1'], $package->getDeclaredDependencies(), $label);
            $this->assertSame(['my-observation', 'my-patient'], array_keys($package->getStructureDefinitions()), $label);
            $this->assertSame(['my-vs'], array_keys($package->getValueSets()), $label);
            $this->assertSame(['my-cs'], array_keys($package->getCodeSystems()), $label);
        }
    }

    public function testFilesAreFoundByContentNotName(): void
    {
        $files = $this->standardFiles();
        $files['.index.json'] = ['index-version' => 1, 'files' => [
            ['filename' => 'terminology.json', 'resourceType' => 'ValueSet', 'id' => 'indexed-vs'],
            // the index is trusted: a file it lists as another type is not decoded.
            ['filename' => 'mislabelled.json', 'resourceType' => 'SearchParameter', 'id' => 'mislabelled'],
        ]];
        $files['terminology.json'] = self::valueSet('indexed-vs');
        $files['mislabelled.json'] = self::structureDefinition('mislabelled');
        $files['us-core-thing.json'] = self::structureDefinition('unindexed');
        $files['example-patient.json'] = ['resourceType' => 'Patient', 'id' => 'example'];
        $sources = [
            'directory' => self::writePackageDir($this->_root . '/named-dir', $files),
            'archive' => self::writePackageArchive($this->_root . '/named.tgz', $files),
        ];
        foreach ($sources as $label => $source) {
            $package = ProfilePackageReader::read($this->config($source));
            $this->assertSame(['my-observation', 'my-patient', 'unindexed'], array_keys($package->getStructureDefinitions()), $label);
            $this->assertSame(['my-vs', 'indexed-vs'], array_keys($package->getValueSets()), $label);
        }
    }

    public function testSpecializationsAreNotProfiles(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', $this->standardFiles());
        $package = ProfilePackageReader::read($this->config($source));
        $this->assertArrayNotHasKey('base-thing', $package->getStructureDefinitions());
        $this->assertNull($package->resolveStructureDefinition('http://example.org/fhir/StructureDefinition/nope'));
        $this->assertNotNull($package->resolveStructureDefinition('http://example.org/fhir/StructureDefinition/base-thing'));
    }

    public function testResolveByCanonicalWithAndWithoutVersion(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', $this->standardFiles());
        $package = ProfilePackageReader::read($this->config($source));
        $url = 'http://example.org/fhir/StructureDefinition/my-patient';
        $this->assertSame('my-patient', $package->resolveStructureDefinition($url)['id']);
        $this->assertSame('my-patient', $package->resolveStructureDefinition($url . '|1.0.0')['id']);
        $this->assertSame('my-vs', $package->resolveValueSet('http://example.org/fhir/ValueSet/my-vs|2')['id']);
        $this->assertSame('my-cs', $package->resolveCodeSystem('http://example.org/fhir/CodeSystem/my-cs')['id']);
        $this->assertNull($package->resolveValueSet('http://example.org/fhir/ValueSet/nope'));
    }

    public function testStructureDefinitionsAreIndexedById(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', $this->standardFiles());
        $all = ProfilePackageReader::read($this->config($source));
        $this->assertSame(['my-observation', 'my-patient'], array_keys($all->getStructureDefinitions()));
    }

    public static function versionProvider(): array
    {
        return [
            'exact' => ['4.0.1', '4.0.1', true],
            'patch differs' => ['4.0.0', '4.0.1', true],
            'v prefix' => ['4.0.1', 'v4.0.1', true],
            'major.minor only' => ['4.0', '4.0.1', true],
            'prerelease' => ['6.0.0-ballot4', 'v6.0.0', true],
            'minor differs' => ['4.3.0', '4.0.1', false],
            'major differs' => ['5.0.0', '4.0.1', false],
            'garbage' => ['R4', '4.0.1', false],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testIsFHIRVersionCompatible(string $packageVersion, string $version, bool $expected): void
    {
        $this->assertSame($expected, ProfilePackageReader::isFHIRVersionCompatible($packageVersion, $version));
    }

    public function testIncompatibleFHIRVersionThrows(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', $this->standardFiles());
        $this->expectException(ProfilePackageException::class);
        $this->expectExceptionMessage('not compatible with 4.3.0');
        ProfilePackageReader::read($this->config($source), '4.3.0');
    }

    public function testAnyDeclaredFHIRVersionMayMatch(): void
    {
        $files = $this->standardFiles();
        $files['package.json'] = self::manifest(overrides: ['fhirVersions' => ['4.0.1', '4.3.0']]);
        $source = self::writePackageDir($this->_root . '/dir', $files);
        $this->assertInstanceOf(ProfilePackage::class, ProfilePackageReader::read($this->config($source), '4.3.0'));
    }

    public function testNoDeclaredFHIRVersionSkipsCheck(): void
    {
        $files = $this->standardFiles();
        unset($files['package.json']['fhirVersions']);
        $source = self::writePackageDir($this->_root . '/dir', $files);
        $package = ProfilePackageReader::read($this->config($source), '5.0.0');
        $this->assertSame([], $package->getFHIRVersions());
    }

    public function testNullFHIRVersionSkipsCheck(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', $this->standardFiles());
        $this->assertInstanceOf(ProfilePackage::class, ProfilePackageReader::read($this->config($source), null));
    }

    public function testMissingManifestThrows(): void
    {
        $files = $this->standardFiles();
        unset($files['package.json']);
        $source = self::writePackageDir($this->_root . '/dir', $files);
        $this->expectException(ProfilePackageException::class);
        $this->expectExceptionMessage('package.json');
        ProfilePackageReader::read($this->config($source));
    }

    public static function badManifestProvider(): array
    {
        return [
            'no name' => [['version' => '1']],
            'no version' => [['name' => 'test.ig']],
            'non-string name' => [['name' => 5, 'version' => '1']],
            'empty version' => [['name' => 'test.ig', 'version' => '']],
        ];
    }

    #[DataProvider('badManifestProvider')]
    public function testInvalidManifestThrows(array $manifest): void
    {
        $source = self::writePackageDir($this->_root . '/dir', ['package.json' => $manifest]);
        $this->expectException(ProfilePackageException::class);
        ProfilePackageReader::read($this->config($source));
    }

    public function testManifestNameMismatchThrows(): void
    {
        $files = $this->standardFiles();
        $files['package.json'] = self::manifest('other.ig');
        $source = self::writePackageDir($this->_root . '/dir', $files);
        $this->expectException(ProfilePackageException::class);
        $this->expectExceptionMessage('other.ig');
        ProfilePackageReader::read($this->config($source));
    }

    public function testInvalidJSONThrows(): void
    {
        $files = $this->standardFiles();
        $files['StructureDefinition-broken.json'] = '{not json';
        $source = self::writePackageDir($this->_root . '/dir', $files);
        $this->expectException(ProfilePackageException::class);
        $this->expectExceptionMessage('StructureDefinition-broken.json');
        ProfilePackageReader::read($this->config($source));
    }

    public function testScalarJSONThrows(): void
    {
        $files = $this->standardFiles();
        $files['ValueSet-scalar.json'] = '5';
        $source = self::writePackageDir($this->_root . '/dir', $files);
        $this->expectException(ProfilePackageException::class);
        ProfilePackageReader::read($this->config($source));
    }

    public function testNonPackageArchiveThrows(): void
    {
        $file = $this->_root . '/garbage.tgz';
        file_put_contents($file, 'definitely not a tarball');
        $this->expectException(ProfilePackageException::class);
        ProfilePackageReader::read($this->config($file));
    }

    public function testArchiveIgnoresFilesOutsidePackageDir(): void
    {
        $files = $this->standardFiles();
        $files['/other/StructureDefinition-outside.json'] = self::structureDefinition('outside');
        $files['/package.json'] = self::manifest('wrong.name');
        $source = self::writePackageArchive($this->_root . '/pkg.tgz', $files);
        $package = ProfilePackageReader::read($this->config($source));
        $this->assertArrayNotHasKey('outside', $package->getStructureDefinitions());
        $this->assertSame('test.ig', $package->getName());
    }

    public function testEmptyPackageIsValid(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', ['package.json' => self::manifest()]);
        $package = ProfilePackageReader::read($this->config($source));
        $this->assertSame([], $package->getStructureDefinitions());
    }

    public function testResolvesThroughProvidedDependencies(): void
    {
        $depFiles = [
            'package.json' => self::manifest('dep.ig', ['dependencies' => []]),
            'ValueSet-dep-vs.json' => self::valueSet('dep-vs'),
            'StructureDefinition-dep-ext.json' => self::structureDefinition('dep-ext', 'Extension'),
        ];
        $dep = ProfilePackageReader::read(
            ProfilePackageConfig::fromArray([
                'name' => 'dep.ig',
                'source' => self::writePackageArchive($this->_root . '/dep.tgz', $depFiles),
            ]),
            '4.0.1',
        );
        $source = self::writePackageDir($this->_root . '/dir', $this->standardFiles());

        $package = ProfilePackageReader::read($this->config($source), '4.0.1', [$dep]);

        $this->assertSame(['dep.ig'], array_keys($package->getLoadedDependencies()));
        $this->assertSame('dep-vs', $package->resolveValueSet('http://example.org/fhir/ValueSet/dep-vs')['id']);
        $this->assertSame(
            'dep-ext',
            $package->resolveStructureDefinition('http://example.org/fhir/StructureDefinition/dep-ext')['id'],
        );
        // dependency resources are not part of this package's own index.
        $this->assertSame(['my-vs'], array_keys($package->getValueSets()));
    }

    public function testStripCanonicalVersion(): void
    {
        $this->assertSame('http://a/b', ProfilePackage::stripCanonicalVersion('http://a/b|1.2.3'));
        $this->assertSame('http://a/b', ProfilePackage::stripCanonicalVersion('http://a/b'));
    }
}
