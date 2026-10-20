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
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use DCarbone\PHPFHIR\Profiles\ProfileRules;
use DCarbone\PHPFHIR\Profiles\ProfileSet;
use DCarbone\PHPFHIR\Profiles\ValueSetExpander;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ProfileRulesTest extends TestCase
{
    use PackageFixtures;

    private const _BASE = 'http://example.org/fhir/StructureDefinition/';

    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfileRulesTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function compile(array $sds, string $id, array $extra = [], bool $expand = false): array
    {
        $files = ['package.json' => self::manifest('test.ig')];
        foreach (array_merge($sds, $extra) as $i => $r) {
            $files[$r['resourceType'] . '-' . $i . '.json'] = $r;
        }
        $path = rtrim($this->_tmpdir->path(''), '/') . '/p-' . uniqid();
        self::writePackageDir($path, $files);
        $package = ProfilePackageReader::read(ProfilePackageConfig::fromArray(['name' => 'test.ig', 'source' => $path]));
        $profile = ProfileSet::fromPackage($package)->getProfileByUrl(self::_BASE . $id);
        return ProfileRules::compile($profile, $expand ? new ValueSetExpander($package) : null);
    }

    private static function el(string $id, array $extra = []): array
    {
        return array_merge(['id' => $id, 'path' => preg_replace('/:[^.]+/', '', $id)], $extra);
    }

    private static function sd(string $id, array $elements, array $overrides = []): array
    {
        return self::structureDefinition($id, 'Patient', 'constraint', array_merge(['differential' => ['element' => $elements]], $overrides));
    }

    public function testCardinalityRules(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient'),
            self::el('Patient.name', ['min' => 1, 'max' => '*']),
            self::el('Patient.birthDate', ['min' => 0, 'max' => '1']),
            self::el('Patient.photo', ['min' => 0, 'max' => '0']),
            self::el('Patient.active', ['min' => 0, 'max' => '*']),
        ])], 'p');
        $this->assertSame([
            ['path' => 'name', 'min' => 1],
            ['path' => 'birthDate', 'max' => 1],
            ['path' => 'photo', 'max' => 0],
        ], $c['rules']);
    }

    public function testChoiceTypesFixedAndPattern(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.deceased[x]', ['type' => [['code' => 'boolean'], ['code' => 'dateTime'], ['code' => 'boolean']]]),
            self::el('Patient.language', ['fixedCode' => 'en']),
            self::el('Patient.maritalStatus', ['patternCodeableConcept' => ['coding' => [['code' => 'M']]]]),
            self::el('Patient.gender', ['type' => [['code' => 'code']]]),
        ])], 'p');
        $this->assertSame(['boolean', 'dateTime'], $c['rules'][0]['types']);
        $this->assertSame('en', $c['rules'][1]['fixed']);
        $this->assertSame(['coding' => [['code' => 'M']]], $c['rules'][2]['pattern']);
        $this->assertCount(3, $c['rules']);
    }

    public function testRequiredBindingIsExpandedWhenPossible(): void
    {
        $vs = array_merge(self::valueSet('g'), ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'a']]]]]]);
        $sds = [self::sd('p', [
            self::el('Patient.gender', ['binding' => ['strength' => 'required', 'valueSet' => 'http://example.org/fhir/ValueSet/g|1']]),
            self::el('Patient.maritalStatus', ['binding' => ['strength' => 'required', 'valueSet' => 'http://example.org/fhir/ValueSet/missing']]),
            self::el('Patient.language', ['binding' => ['strength' => 'extensible', 'valueSet' => 'http://example.org/fhir/ValueSet/g']]),
        ])];

        $c = $this->compile($sds, 'p', [$vs], true);
        $this->assertSame([['s', 'a']], $c['rules'][0]['codes']);
        $this->assertCount(1, $c['rules']);
        $this->assertCount(1, $c['skipped']);
        $this->assertStringContainsString('Patient.maritalStatus', $c['skipped'][0]);

        $noExpander = $this->compile($sds, 'p', [$vs], false);
        $this->assertSame([], $noExpander['rules']);
        $this->assertCount(2, $noExpander['skipped']);
    }

    public function testExtensionSliceMatchesOnProfileUrl(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.extension', ['slicing' => ['discriminator' => [['type' => 'value', 'path' => 'url']], 'rules' => 'open']]),
            self::el('Patient.extension:race', ['sliceName' => 'race', 'min' => 0, 'max' => '1', 'type' => [['code' => 'Extension', 'profile' => ['http://x/race|1.0']]]]),
        ])], 'p');
        $this->assertSame([], $c['rules']);
        $this->assertSame([['path' => 'extension', 'name' => 'race', 'match' => ['url' => 'http://x/race'], 'max' => 1]], $c['slices']);
    }

    public function testValueSliceMatchesChildAndOwnPatterns(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.identifier:ssn', ['sliceName' => 'ssn', 'min' => 1, 'max' => '1']),
            self::el('Patient.identifier:ssn.system', ['fixedUri' => 'http://ssa.gov']),
            self::el('Patient.contact:vip', ['sliceName' => 'vip', 'patternCodeableConcept' => ['coding' => [['code' => 'V']]]]),
            self::el('Patient.contact:vip.relationship.coding.code', ['fixedCode' => 'X']),
        ])], 'p');
        $this->assertSame([
            ['path' => 'identifier', 'name' => 'ssn', 'match' => ['system' => 'http://ssa.gov'], 'min' => 1, 'max' => 1],
            ['path' => 'contact', 'name' => 'vip', 'match' => ['coding' => [['code' => 'V']], 'relationship' => ['coding' => ['code' => 'X']]]],
        ], $c['slices']);
    }

    public function testDiscriminatorsLimitMatchToTheirPaths(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.identifier', ['slicing' => ['discriminator' => [['type' => 'value', 'path' => 'system']], 'rules' => 'open']]),
            self::el('Patient.identifier:ssn', ['sliceName' => 'ssn', 'min' => 1]),
            self::el('Patient.identifier:ssn.system', ['fixedUri' => 'http://ssa.gov']),
            self::el('Patient.identifier:ssn.use', ['fixedCode' => 'official']),
            self::el('Patient.contact', ['slicing' => ['discriminator' => [['type' => 'pattern', 'path' => 'relationship.coding.code']], 'rules' => 'open']]),
            self::el('Patient.contact:vip', ['sliceName' => 'vip']),
            self::el('Patient.contact:vip.relationship', ['patternCodeableConcept' => ['coding' => [['code' => 'V']]]]),
            self::el('Patient.contact:vip.gender', ['fixedCode' => 'other']),
        ])], 'p');
        $this->assertSame([
            ['path' => 'identifier', 'name' => 'ssn', 'match' => ['system' => 'http://ssa.gov'], 'min' => 1],
            ['path' => 'contact', 'name' => 'vip', 'match' => ['relationship' => ['coding' => [['code' => 'V']]]]],
        ], $c['slices']);
    }

    public function testDiscriminatorOnThisAndInheritedSlicing(): void
    {
        $c = $this->compile([
            self::sd('base', [
                self::el('Patient.identifier', ['slicing' => ['discriminator' => [['type' => 'pattern', 'path' => '$this']], 'rules' => 'open']]),
            ]),
            self::sd('child', [
                self::el('Patient.identifier:mrn', ['sliceName' => 'mrn', 'patternIdentifier' => ['system' => 'http://mrn']]),
                self::el('Patient.identifier:mrn.use', ['fixedCode' => 'usual']),
            ], ['baseDefinition' => self::_BASE . 'base']),
        ], 'child');
        $this->assertSame([['path' => 'identifier', 'name' => 'mrn', 'match' => ['system' => 'http://mrn']]], $c['slices']);
    }

    public function testUnsupportedOrUnfixedDiscriminatorSkipsSlice(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.extension', ['slicing' => ['discriminator' => [['type' => 'type', 'path' => '$this']], 'rules' => 'open']]),
            self::el('Patient.extension:a', ['sliceName' => 'a', 'type' => [['code' => 'Extension', 'profile' => ['http://x/a']]]]),
            self::el('Patient.identifier', ['slicing' => ['discriminator' => [['type' => 'value', 'path' => 'system']], 'rules' => 'open']]),
            self::el('Patient.identifier:b', ['sliceName' => 'b']),
            self::el('Patient.identifier:b.use', ['fixedCode' => 'usual']),
            self::el('Patient.contact', ['slicing' => ['discriminator' => [['type' => 'value', 'path' => "extension('http://x').value"]], 'rules' => 'open']]),
            self::el('Patient.contact:c', ['sliceName' => 'c']),
        ])], 'p');
        $this->assertSame([], $c['slices']);
    }

    public function testUnmatchableSliceIsSkippedAndWithinSliceElementsIgnored(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.name:legal', ['sliceName' => 'legal', 'min' => 1]),
            self::el('Patient.name:legal.family', ['min' => 1]),
            self::el('Patient.extension:race.extension:detail', ['sliceName' => 'detail', 'type' => [['code' => 'Extension', 'profile' => ['http://x/d']]]]),
        ])], 'p');
        $this->assertSame([], $c['rules']);
        $this->assertSame([], $c['slices']);
        $this->assertCount(1, $c['skipped']);
    }

    public function testSnapshotValuesAndParentChanges(): void
    {
        $parent = self::sd('parent-p', [self::el('Patient.name', ['min' => 1])]);
        $child = self::sd('child-p', [self::el('Patient.birthDate', ['min' => 1])], [
            'baseDefinition' => self::_BASE . 'parent-p',
            'snapshot' => ['element' => [
                self::el('Patient'),
                self::el('Patient.name', ['min' => 1, 'max' => '*']),
                self::el('Patient.birthDate', ['min' => 1, 'max' => '1']),
                self::el('Patient.photo', ['min' => 0, 'max' => '*']),
            ]],
        ]);
        $c = $this->compile([$parent, $child], 'child-p');
        $this->assertSame([
            ['path' => 'birthDate', 'min' => 1, 'max' => 1],
            ['path' => 'name', 'min' => 1],
        ], $c['rules']);
    }

    public function testInvariantsAreCarriedAsMetadata(): void
    {
        $c = $this->compile([self::sd('p', [
            self::el('Patient.name', ['constraint' => [['key' => 'x-1', 'severity' => 'error', 'human' => 'Must be so', 'expression' => 'name.exists()']]]),
        ])], 'p');
        $this->assertSame([['key' => 'x-1', 'severity' => 'error', 'human' => 'Must be so', 'expression' => 'name.exists()', 'path' => 'name']], $c['invariants']);
        $this->assertSame([], $c['rules']);
    }
}
