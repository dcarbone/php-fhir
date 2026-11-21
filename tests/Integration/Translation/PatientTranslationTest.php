<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Integration\Translation;

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIRGenerated\Translation\OnUnmappableFieldEnum;
use DCarbone\PHPFHIRGenerated\Translation\TranslationConfig;
use DCarbone\PHPFHIRGenerated\Translation\TranslationNotice;
use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRResource\FHIRPatient as DSTU1Patient;
use DCarbone\PHPFHIRGenerated\Versions\DSTU2\Types\FHIRResource\FHIRDomainResource\FHIRPatient as DSTU2Patient;
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIRPatient as R4Patient;
use DCarbone\PHPFHIRGenerated\Versions\R4B\Types\FHIRResource\FHIRDomainResource\FHIRPatient as R4BPatient;
use DCarbone\PHPFHIRGenerated\Versions\R5\Types\FHIRBase\FHIRResource\FHIRDomainResource\FHIRPatient as R5Patient;
use DCarbone\PHPFHIRGenerated\Versions\STU3\Types\FHIRResource\FHIRDomainResource\FHIRPatient as STU3Patient;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Translates a Patient across every generated version using only the default translators.
 */
class PatientTranslationTest extends AbstractTranslationIntegrationTestCase
{
    /**
     * @return array<string, array{string, class-string, array<string, mixed>}>
     */
    public static function r4PatientTargetProvider(): array
    {
        $expected = [
            'resourceType' => 'Patient',
            'id' => 'p1',
            'active' => true,
            'name' => [['family' => 'Doe', 'given' => ['Jane']]],
            'gender' => 'female',
            'birthDate' => '1970-01-02',
        ];
        $dstu2 = $expected;
        $dstu2['name'] = [['family' => ['Doe'], 'given' => ['Jane']]];
        return [
            'R4 to DSTU2' => ['DSTU2', DSTU2Patient::class, $dstu2],
            'R4 to STU3' => ['STU3', STU3Patient::class, $expected],
            'R4 to R4B' => ['R4B', R4BPatient::class, $expected],
            'R4 to R5' => ['R5', R5Patient::class, $expected],
        ];
    }

    #[DataProvider('r4PatientTargetProvider')]
    public function testR4PatientTranslatesWithoutLoss(string $target, string $expectedClass, array $expectedJSON): void
    {
        $result = (new Translator())->translate(self::parse('R4', self::R4_PATIENT_JSON), self::version($target));

        $resource = $result->getResource();
        self::assertInstanceOf($expectedClass, $resource);
        self::assertResourceVersion($target, $resource);
        self::assertEquals($expectedJSON, self::decode($resource));
        self::assertSame([], $result->getNotices());
    }

    public function testSameVersionReturnsSourceUnchanged(): void
    {
        $source = self::parse('R4', self::R4_PATIENT_JSON);

        $result = (new Translator())->translate($source, self::version('R4'));

        self::assertSame($source, $result->getResource());
        self::assertSame([], $result->getNotices());
    }

    public function testR4PatientRoundTripsThroughR5(): void
    {
        $translator = new Translator();
        $r5 = $translator->translate(self::parse('R4', self::R4_PATIENT_JSON), self::version('R5'))->getResource();

        $result = $translator->translate($r5, self::version('R4'));

        self::assertInstanceOf(R4Patient::class, $result->getResource());
        self::assertEquals(json_decode(self::R4_PATIENT_JSON, true), self::decode($result->getResource()));
        self::assertSame([], $result->getNotices());
    }

    public function testDSTU1PatientChainsAllTheWayToR5(): void
    {
        $source = self::parse('DSTU1', '{"resourceType":"Patient","id":"p1","active":true,"name":[{"family":["Doe"],"given":["Jane"]}],"birthDate":"1970-01-02"}');

        $result = (new Translator())->translate($source, self::version('R5'));

        // DSTU1 birthDate is a dateTime and DSTU1/DSTU2 family is repeating; both are coerced by target field type.
        self::assertInstanceOf(R5Patient::class, $result->getResource());
        self::assertEquals(
            [
                'resourceType' => 'Patient',
                'id' => 'p1',
                'active' => true,
                'name' => [['family' => 'Doe', 'given' => ['Jane']]],
                'birthDate' => '1970-01-02',
            ],
            self::decode($result->getResource()),
        );
        self::assertSame([], $result->getNotices());
    }

    public function testDateTimeWithTimePrecisionIsUnmappableToFHIRDate(): void
    {
        $source = self::parse(
            'DSTU1',
            '{"resourceType":"Patient","birthDate":"1970-01-02T12:34:56Z"}',
        );
        $result = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP)))
            ->translate($source, self::version('R5'));
        $noticePaths = array_map(
            static fn(TranslationNotice $notice): string => $notice->getPath(),
            $result->getNotices(),
        );

        self::assertArrayNotHasKey('birthDate', self::decode($result->getResource()));
        self::assertContains('Patient.birthDate.value', $noticePaths);
    }

    public function testR5PatientChainsAllTheWayToDSTU1(): void
    {
        $source = self::parse('R5', '{"resourceType":"Patient","id":"p1","active":true,"name":[{"family":"Doe","given":["Jane"]}],"birthDate":"1970-01-02"}');

        $result = (new Translator())->translate($source, self::version('DSTU1'));

        self::assertInstanceOf(DSTU1Patient::class, $result->getResource());
        self::assertEquals(
            [
                'resourceType' => 'Patient',
                'id' => 'p1',
                'active' => true,
                'name' => [['family' => ['Doe'], 'given' => ['Jane']]],
                'birthDate' => '1970-01-02',
            ],
            self::decode($result->getResource()),
        );
        self::assertSame([], $result->getNotices());
    }

    public function testGenderIsDroppedWithNoticesWhenTranslatingToDSTU1(): void
    {
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));

        $result = $translator->translate(self::parse('R4', self::R4_PATIENT_JSON), self::version('DSTU1'));

        // DSTU1 gender is a CodeableConcept, which the default translator will not invent from a bare code.
        $decoded = self::decode($result->getResource());
        self::assertArrayNotHasKey('gender', $decoded);
        self::assertSame('1970-01-02', $decoded['birthDate']);
        self::assertSame(
            ['Patient.gender.value DSTU2->DSTU1', 'Patient.gender DSTU2->DSTU1'],
            self::noticePaths($result),
        );
        foreach ($result->getNotices() as $notice) {
            self::assertSame('dropped', $notice->getKind());
        }
    }
}
