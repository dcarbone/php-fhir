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
use DCarbone\PHPFHIR\Profiles\Profile;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProfileTest extends TestCase
{
    use PackageFixtures;

    private static function element(string $id, array $extra = []): array
    {
        return array_merge(['id' => $id, 'path' => preg_replace('{:[^.]+}', '', $id)], $extra);
    }

    private static function patientSD(array $overrides = []): array
    {
        return self::structureDefinition('my-patient', 'Patient', 'constraint', array_merge([
            'title' => 'My Patient',
            'version' => '1.2.3',
            'status' => 'active',
            'description' => 'desc',
            'differential' => ['element' => [
                self::element('Patient'),
                self::element('Patient.extension', ['slicing' => ['discriminator' => [['type' => 'value', 'path' => 'url']], 'rules' => 'open']]),
                self::element('Patient.extension:race', ['sliceName' => 'race', 'min' => 0, 'max' => '1']),
                self::element('Patient.extension:race.url'),
                self::element('Patient.extension:ethnicity', ['sliceName' => 'ethnicity']),
                self::element('Patient.identifier', ['min' => 1]),
            ]],
        ], $overrides));
    }

    public function testFromStructureDefinition(): void
    {
        $p = new Profile(self::patientSD());
        $this->assertSame('my-patient', $p->getId());
        $this->assertSame('http://example.org/fhir/StructureDefinition/my-patient', $p->getUrl());
        $this->assertSame('MyPatient', $p->getName());
        $this->assertSame('My Patient', $p->getTitle());
        $this->assertSame('1.2.3', $p->getVersion());
        $this->assertSame('active', $p->getStatus());
        $this->assertSame('desc', $p->getDescription());
        $this->assertSame('Patient', $p->getType());
        $this->assertSame('resource', $p->getKind());
        $this->assertFalse($p->isExtension());
        $this->assertFalse($p->isAbstract());
        $this->assertSame('http://hl7.org/fhir/StructureDefinition/Patient', $p->getBaseDefinition());
        $this->assertTrue($p->isBasedOnBaseType());
        $this->assertFalse($p->hasParent());
        $this->assertNull($p->getParent());
        $this->assertSame([], $p->getAncestors());
    }

    public function testElementsWithoutSnapshotUseDifferential(): void
    {
        $p = new Profile(self::patientSD());
        $this->assertFalse($p->hasSnapshot());
        $this->assertSame([], $p->getSnapshotElements());
        $this->assertSame($p->getDifferentialElements(), $p->getElements());
        $this->assertSame(1, $p->getElement('Patient.identifier')->getMin());
        $this->assertNull($p->getElement('Patient.nope'));
        $this->assertNotNull($p->getDifferentialElement('Patient.identifier'));
    }

    public function testSnapshotPreferredForElements(): void
    {
        $sd = self::patientSD(['snapshot' => ['element' => [
            self::element('Patient'),
            self::element('Patient.identifier', ['min' => 1]),
            self::element('Patient.name', ['min' => 1]),
        ]]]);
        $p = new Profile($sd);
        $this->assertTrue($p->hasSnapshot());
        $this->assertCount(3, $p->getSnapshotElements());
        $this->assertCount(6, $p->getDifferentialElements());
        $this->assertSame($p->getSnapshotElements(), $p->getElements());
        $this->assertNotNull($p->getElement('Patient.name'));
        $this->assertNull($p->getDifferentialElement('Patient.name'));
    }

    public function testVersionedBaseTypeDefinitionIsBaseType(): void
    {
        $p = new Profile(self::patientSD(['baseDefinition' => 'http://hl7.org/fhir/StructureDefinition/Patient|4.0.1']));
        $this->assertTrue($p->isBasedOnBaseType());
    }

    public function testDuplicateSnapshotElementsKeepFirst(): void
    {
        $sd = self::patientSD(['snapshot' => ['element' => [
            self::element('Patient'),
            self::element('Patient.identifier', ['min' => 1]),
            self::element('Patient.identifier', ['min' => 2]),
        ]]]);
        $p = new Profile($sd);
        $this->assertCount(2, $p->getSnapshotElements());
        $this->assertSame(1, $p->getSnapshotElements()['Patient.identifier']->getMin());
    }

    public function testChildrenAndSlices(): void
    {
        $p = new Profile(self::patientSD());
        $this->assertSame(
            ['Patient.extension', 'Patient.extension:race', 'Patient.extension:ethnicity', 'Patient.identifier'],
            array_map(fn($e) => $e->getId(), $p->getChildren('Patient')),
        );
        $this->assertSame(['race', 'ethnicity'], array_keys($p->getSlices('Patient.extension')));
        $this->assertSame(['Patient.extension:race.url'], array_map(fn($e) => $e->getId(), $p->getChildren('Patient.extension:race')));
        // children of a slice are not themselves slices of the parent element.
        $this->assertSame([], $p->getSlices('Patient.identifier'));
    }

    public function testNestedSlicesAreScopedToTheirParent(): void
    {
        $sd = self::structureDefinition('obs', 'Observation', 'constraint', ['differential' => ['element' => [
            self::element('Observation.component:a', ['sliceName' => 'a']),
            self::element('Observation.component:a.extension:x', ['sliceName' => 'x']),
            self::element('Observation.component:b', ['sliceName' => 'b']),
        ]]]);
        $p = new Profile($sd);
        $this->assertSame(['a', 'b'], array_keys($p->getSlices('Observation.component')));
        $this->assertSame(['x'], array_keys($p->getSlices('Observation.component:a.extension')));
    }

    public function testExtensionProfile(): void
    {
        $sd = self::structureDefinition('my-ext', 'Extension', 'constraint', [
            'kind' => 'complex-type',
            'context' => [['type' => 'element', 'expression' => 'Patient'], ['bogus' => true]],
        ]);
        $p = new Profile($sd);
        $this->assertTrue($p->isExtension());
        $this->assertSame('complex-type', $p->getKind());
        $this->assertSame([['type' => 'element', 'expression' => 'Patient']], $p->getContext());
    }

    public function testEmptyDifferentialIsValid(): void
    {
        $p = new Profile(self::structureDefinition('empty'));
        $this->assertSame([], $p->getElements());
    }

    public function testBasedOnProfileIsNotBasedOnBaseType(): void
    {
        $p = new Profile(self::structureDefinition('child', 'Patient', 'constraint', [
            'baseDefinition' => 'http://example.org/fhir/StructureDefinition/my-patient',
        ]));
        $this->assertFalse($p->isBasedOnBaseType());
    }

    public static function invalidSDProvider(): array
    {
        $ok = self::structureDefinition('x');
        $without = static function (string $k) use ($ok): array {
            unset($ok[$k]);
            return $ok;
        };
        return [
            'no id' => [$without('id')],
            'no url' => [$without('url')],
            'no name' => [$without('name')],
            'no type' => [$without('type')],
            'no kind' => [$without('kind')],
            'no baseDefinition' => [$without('baseDefinition')],
            'no derivation' => [$without('derivation')],
            'wrong resourceType' => [array_merge($ok, ['resourceType' => 'ValueSet'])],
            'specialization' => [array_merge($ok, ['derivation' => 'specialization'])],
            'no element lists' => [array_diff_key($ok, ['differential' => 1])],
            'non-array elements' => [array_merge($ok, ['differential' => ['element' => 'nope']])],
            'invalid element' => [array_merge($ok, ['differential' => ['element' => [['id' => 'A']]]])],
        ];
    }

    #[DataProvider('invalidSDProvider')]
    public function testInvalidStructureDefinitionThrows(array $sd): void
    {
        $this->expectException(ProfileDefinitionException::class);
        new Profile($sd);
    }

    public function testElementErrorMessageNamesProfile(): void
    {
        $sd = self::structureDefinition('named', 'Patient', 'constraint', ['differential' => ['element' => [['id' => 'A']]]]);
        $this->expectExceptionMessage('"named"');
        new Profile($sd);
    }

    public function testCopyright(): void
    {
        $this->assertNull((new Profile(self::patientSD()))->getCopyright());
        $this->assertNull((new Profile(self::patientSD(['copyright' => '  '])))->getCopyright());
        $this->assertSame('(c) HL7', (new Profile(self::patientSD(['copyright' => " (c) HL7\n"])))->getCopyright());
    }
}
