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

use DCarbone\PHPFHIRGenerated\Translation\MissingXverDefinitionException;
use DCarbone\PHPFHIRGenerated\Translation\OnUnmappableFieldEnum;
use DCarbone\PHPFHIRGenerated\Translation\TranslationConfig;
use DCarbone\PHPFHIRGenerated\Translation\Translator;

/**
 * Exercises the EXTENSION unmappable-field policy against the real HL7 xver packages ingested for R5 -> R4.
 */
class XverExtensionEmissionTest extends AbstractTranslationIntegrationTestCase
{
    private const EXTENSION_URL_PREFIX = 'http://hl7.org/fhir/5.0/StructureDefinition/extension-';

    private const R5_ACCOUNT_JSON = '{"resourceType":"Account","id":"a1","status":"active","name":"Main",'
        . '"currency":{"coding":[{"system":"urn:iso:std:iso:4217","code":"USD"}]},'
        . '"balance":[{"aggregate":{"coding":[{"system":"http://example.org","code":"patient"}]},'
        . '"term":{"coding":[{"system":"http://example.org","code":"long"}]},'
        . '"estimate":true,"amount":{"value":12.5,"currency":"USD"}}]}';

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function extensionsOf(array $resource, string $element = 'extension'): array
    {
        return $resource[$element] ?? [];
    }

    private static function translateAccount(string $json, string $target = 'R4'): array
    {
        $result = (new Translator())->translate(self::parse('R5', $json), self::version($target));

        return self::decode($result->getResource());
    }

    public function testASimpleExtensionCarriesAFieldTheTargetVersionLacks(): void
    {
        $account = self::translateAccount(self::R5_ACCOUNT_JSON);

        self::assertArrayNotHasKey('currency', $account);
        $extensions = self::extensionsOf($account);
        $currency = array_values(array_filter(
            $extensions,
            static fn(array $e): bool => self::EXTENSION_URL_PREFIX . 'Account.currency' === $e['url'],
        ));
        self::assertCount(1, $currency);
        self::assertSame(
            ['coding' => [['system' => 'urn:iso:std:iso:4217', 'code' => 'USD']]],
            $currency[0]['valueCodeableConcept'],
        );
    }

    public function testAComplexExtensionCarriesNestedValuesAndTheirDatatype(): void
    {
        $account = self::translateAccount(self::R5_ACCOUNT_JSON);

        self::assertArrayNotHasKey('balance', $account);
        $balance = array_values(array_filter(
            self::extensionsOf($account),
            static fn(array $e): bool => self::EXTENSION_URL_PREFIX . 'Account.balance' === $e['url'],
        ));
        self::assertCount(1, $balance);
        $children = [];
        foreach ($balance[0]['extension'] as $child) {
            $children[$child['url']] = $child;
        }
        self::assertSame(true, $children['estimate']['valueBoolean']);
        self::assertSame('long', $children['term']['valueCodeableConcept']['coding'][0]['code']);
        self::assertEquals(12.5, $children['amount']['valueMoney']['value']);
    }

    public function testTranslatedFieldsAreStillCopiedNormally(): void
    {
        $account = self::translateAccount(self::R5_ACCOUNT_JSON);

        self::assertSame('Account', $account['resourceType']);
        self::assertSame('active', $account['status']);
        self::assertSame('Main', $account['name']);
    }

    public function testDropPolicyDoesNotEmitExtensions(): void
    {
        $result = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP)))
            ->translate(self::parse('R5', self::R5_ACCOUNT_JSON), self::version('R4'));

        $account = self::decode($result->getResource());
        self::assertArrayNotHasKey('extension', $account);
        self::assertContains('Account.currency R5->R4', self::noticePaths($result));
        self::assertContains('Account.balance R5->R4', self::noticePaths($result));
    }

    public function testEachHopUsesItsOwnPackageAndSourceVersionUrl(): void
    {
        $result = (new Translator())->translate(
            self::parse('R4', '{"resourceType":"Account","id":"a1","status":"active","partOf":{"reference":"Account/p1"}}'),
            self::version('STU3'),
        );

        $account = self::decode($result->getResource());
        self::assertArrayNotHasKey('partOf', $account);
        self::assertEquals(
            [['url' => 'http://hl7.org/fhir/4.0/StructureDefinition/extension-Account.partOf', 'valueReference' => ['reference' => 'Account/p1']]],
            self::extensionsOf($account),
        );
    }

    public function testHopsWithoutAnXverPackageStillRequireADefinition(): void
    {
        $this->expectException(MissingXverDefinitionException::class);
        (new Translator())->translate(self::parse('R4', self::R4_PATIENT_JSON), self::version('DSTU1'));
    }
}
