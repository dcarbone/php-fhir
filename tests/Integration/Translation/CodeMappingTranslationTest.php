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

use DCarbone\PHPFHIRGenerated\Translation\HopMapRegistry;
use DCarbone\PHPFHIRGenerated\Translation\OnUnmappableFieldEnum;
use DCarbone\PHPFHIRGenerated\Translation\TranslationConfig;
use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Translation\UnmappableFieldException;
use DCarbone\PHPFHIRGenerated\Types\TypeInterface;
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIRBasic as R4Basic;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exercises Coding translation driven by the HL7 xver ConceptMaps ingested for the R5 -> R4 hop.
 */
class CodeMappingTranslationTest extends AbstractTranslationIntegrationTestCase
{
    private const R5_BASIC_JSON = '{"resourceType":"Basic","id":"b1","code":{"coding":['
        . '{"system":"http://hl7.org/fhir/encounter-status","version":"5.0.0","code":"on-hold"},'
        . '{"system":"http://hl7.org/fhir/fhir-types","version":"5.0.0","code":"DeviceUsage"},'
        . '{"system":"http://hl7.org/fhir/observation-status","version":"5.0.0","code":"mitigated"},'
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
                ['system' => 'http://hl7.org/fhir/encounter-status', 'version' => '4.0.1', 'code' => 'onleave'],
                // fhir-types has several ConceptMaps; only the one containing the code is used.
                ['system' => 'http://hl7.org/fhir/resource-types', 'version' => '4.0.1', 'code' => 'DeviceUseStatement'],
                // observation-status "mitigated" has no R4 equivalent and is dropped.
                // Systems without a ConceptMap pass through untouched.
                ['system' => 'http://example.org', 'code' => 'x'],
            ],
            self::decode($result->getResource())['code']['coding'],
        );
        self::assertSame(
            ['Basic.code.coding.code R5->R4', 'Basic.code.coding.version R5->R4', 'Basic.code.coding R5->R4'],
            self::noticePaths($result),
        );
    }

    public function testCodeSystemChangeUsesTheMappedTargetVersion(): void
    {
        $source = self::parse(
            'R5',
            '{"resourceType":"Basic","code":{"coding":[{"system":"http://hl7.org/fhir/fhir-types",'
            . '"version":"5.0.0","code":"DeviceUsage"}]}}',
        );

        $result = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP)))
            ->translate($source, self::version('R4'));
        $coding = self::decode($result->getResource())['code']['coding'][0];

        self::assertSame('http://hl7.org/fhir/resource-types', $coding['system']);
        self::assertSame('DeviceUseStatement', $coding['code']);
        self::assertSame('4.0.1', $coding['version']);
        self::assertSame([], $result->getNotices());
    }

    public function testCodingPrimitiveContainersAreUnwrappedWhenTheHopHasCodeMaps(): void
    {
        $source = self::parse(
            'R5',
            '{"resourceType":"Basic","id":"b2","code":{"coding":['
            . '{"system":"http://hl7.org/fhir/encounter-status","version":"5.0.0","code":"on-hold"},'
            . '{"system":"http://example.org","code":"123"}'
            . ']}}',
        );
        $coding = $source->getCode()->getCoding()[0];
        // Coding.system and Coding.code are primitive containers wrapping a primitive object, not a string.
        self::assertInstanceOf(TypeInterface::class, $coding->getSystem()->getValue());
        self::assertInstanceOf(TypeInterface::class, $coding->getCode()->getValue());
        self::assertNotSame([], HopMapRegistry::getMap(self::version('R5'), self::version('R4'))?->getCodeMaps() ?? []);

        $result = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW)))
            ->translate($source, self::version('R4'));

        self::assertSame(
            [
                ['system' => 'http://hl7.org/fhir/encounter-status', 'version' => '4.0.1', 'code' => 'onleave'],
                ['system' => 'http://example.org', 'code' => '123'],
            ],
            self::decode($result->getResource())['code']['coding'],
        );
        self::assertSame([], $result->getNotices());
    }

    /** @return array<string, array{null|string}> */
    public static function incompatibleSourceVersionsProvider(): array
    {
        return [
            'missing source version' => [null],
            'different source version' => ['4.0.1'],
        ];
    }

    #[DataProvider('incompatibleSourceVersionsProvider')]
    public function testVersionSpecificMapsRejectMissingOrDifferentSourceVersions(null|string $version): void
    {
        $source = self::parse(
            'R5',
            '{"resourceType":"Basic","code":{"coding":[{"system":"http://hl7.org/fhir/encounter-status","code":"on-hold"'
            . (null === $version ? '' : ',"version":' . json_encode($version, JSON_THROW_ON_ERROR))
            . '}]}}',
        );

        $this->expectException(UnmappableFieldException::class);
        $this->expectExceptionMessage('Basic.code.coding.code');
        (new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW)))
            ->translate($source, self::version('R4'));
    }

    public function testR5ToStu3UsesPackageBackedIntermediateMaps(): void
    {
        $source = self::parse(
            'R5',
            '{"resourceType":"Basic","code":{"coding":[{"system":"http://hl7.org/fhir/encounter-status",'
            . '"version":"5.0.0","code":"on-hold"}]}}',
        );

        $result = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW)))
            ->translate($source, self::version('STU3'));

        self::assertResourceVersion('STU3', $result->getResource());
        // R4 -> STU3 has no map for this system, so the R4 code-system version is retained.
        self::assertSame([
            ['system' => 'http://hl7.org/fhir/encounter-status', 'version' => '4.0.1', 'code' => 'onleave'],
        ], self::decode($result->getResource())['code']['coding']);
        self::assertSame([], $result->getNotices());
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
