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
use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIRBasic as R4Basic;

/**
 * Exercises Coding translation driven by the HL7 xver ConceptMaps ingested for the R5 -> R4 hop.
 */
class CodeMappingTranslationTest extends AbstractTranslationIntegrationTestCase
{
    private const R5_BASIC_JSON = '{"resourceType":"Basic","id":"b1","code":{"coding":['
        . '{"system":"http://hl7.org/fhir/encounter-status","code":"on-hold"},'
        . '{"system":"http://hl7.org/fhir/fhir-types","code":"DeviceUsage"},'
        . '{"system":"http://hl7.org/fhir/observation-status","code":"mitigated"},'
        . '{"system":"http://example.org","code":"x"}'
        . ']}}';

    public function testR5ToR4CodingsAreMappedThroughXverConceptMaps(): void
    {
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));

        $result = $translator->translate(self::parse('R5', self::R5_BASIC_JSON), self::version('R4'));

        self::assertInstanceOf(R4Basic::class, $result->getResource());
        self::assertSame(
            [
                // Mapped by the encounter-status ConceptMap.
                ['system' => 'http://hl7.org/fhir/encounter-status', 'code' => 'onleave'],
                // fhir-types has several ConceptMaps; only the one containing the code is used.
                ['system' => 'http://hl7.org/fhir/resource-types', 'code' => 'DeviceUseStatement'],
                // observation-status "mitigated" has no R4 equivalent and is dropped.
                // Systems without a ConceptMap pass through untouched.
                ['system' => 'http://example.org', 'code' => 'x'],
            ],
            self::decode($result->getResource())['code']['coding'],
        );
        self::assertSame(
            ['Basic.code.coding.code R5->R4', 'Basic.code.coding R5->R4'],
            self::noticePaths($result),
        );
    }

    public function testHopsWithoutConceptMapsCopyCodesVerbatim(): void
    {
        $source = self::parse('R5', self::R5_BASIC_JSON);

        $result = (new Translator())->translate($source, self::version('R4B'));

        self::assertSame(
            self::decode($source)['code']['coding'],
            self::decode($result->getResource())['code']['coding'],
        );
        self::assertSame([], $result->getNotices());
    }
}
