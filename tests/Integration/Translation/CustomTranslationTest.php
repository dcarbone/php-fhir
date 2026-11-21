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

use DCarbone\PHPFHIRGenerated\Translation\FieldMetadataFactory;
use DCarbone\PHPFHIRGenerated\Translation\OnUnmappableFieldEnum;
use DCarbone\PHPFHIRGenerated\Translation\TranslationConfig;
use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRResource\FHIRPatient as DSTU1Patient;
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIREncounter as R4Encounter;
use DCarbone\PHPFHIRTests\Integration\Translation\Fixtures\DSTU2ToDSTU1PatientGenderTranslator;
use DCarbone\PHPFHIRTests\Integration\Translation\Fixtures\ExcludingFieldMetadataFactory;
use DCarbone\PHPFHIRTests\Integration\Translation\Fixtures\R5ToR4EncounterStatusTranslator;

/**
 * Demonstrates user-supplied translators and metadata factories working against real generated code.
 */
class CustomTranslationTest extends AbstractTranslationIntegrationTestCase
{
    private const R5_ENCOUNTER_JSON = '{"resourceType":"Encounter","id":"e1","status":"on-hold"}';

    public function testDefaultTranslatorCopiesBareCodesVerbatim(): void
    {
        $result = (new Translator())->translate(self::parse('R5', self::R5_ENCOUNTER_JSON), self::version('R4'));

        self::assertSame('on-hold', self::decode($result->getResource())['status']);
    }

    public function testRegisteredTranslatorFixesUpBareCodes(): void
    {
        $translator = (new Translator())->register(
            self::version('R5'),
            self::version('R4'),
            'Encounter',
            new R5ToR4EncounterStatusTranslator(),
        );

        $result = $translator->translate(self::parse('R5', self::R5_ENCOUNTER_JSON), self::version('R4'));

        self::assertInstanceOf(R4Encounter::class, $result->getResource());
        self::assertEquals(
            ['resourceType' => 'Encounter', 'id' => 'e1', 'status' => 'onleave'],
            self::decode($result->getResource()),
        );
        self::assertSame([], $result->getNotices());
    }

    public function testRegisteredTranslatorParticipatesInMultiHopChain(): void
    {
        $translator = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW)))->register(
            self::version('DSTU2'),
            self::version('DSTU1'),
            'Patient',
            new DSTU2ToDSTU1PatientGenderTranslator(),
        );

        // R4 -> R4B -> ... -> DSTU2 use the defaults; only the final DSTU2 -> DSTU1 hop uses the custom translator.
        $result = $translator->translate(self::parse('R4', self::R4_PATIENT_JSON), self::version('DSTU1'));

        self::assertInstanceOf(DSTU1Patient::class, $result->getResource());
        $decoded = self::decode($result->getResource());
        self::assertSame(
            [
                'coding' => [[
                    'system' => DSTU2ToDSTU1PatientGenderTranslator::GENDER_SYSTEM,
                    'code' => 'F',
                    'display' => 'Female',
                ]],
            ],
            $decoded['gender'],
        );
        self::assertSame('1970-01-02', $decoded['birthDate']);
        self::assertSame([['family' => ['Doe'], 'given' => ['Jane']]], $decoded['name']);
        self::assertSame([], $result->getNotices());
    }

    public function testCustomFieldMetadataFactoryControlsCopiedFields(): void
    {
        $translator = new Translator(
            new TranslationConfig(),
            new ExcludingFieldMetadataFactory(new FieldMetadataFactory(), 'active', 'birthDate'),
        );

        $result = $translator->translate(self::parse('R4', self::R4_PATIENT_JSON), self::version('R5'));

        $decoded = self::decode($result->getResource());
        self::assertArrayNotHasKey('active', $decoded);
        self::assertArrayNotHasKey('birthDate', $decoded);
        self::assertSame('female', $decoded['gender']);
        self::assertSame('p1', $decoded['id']);
    }
}
