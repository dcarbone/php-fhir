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

use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Translation\FieldMetadataFactory;
use DCarbone\PHPFHIRGenerated\Translation\HopMapRegistry;
use DCarbone\PHPFHIRGenerated\Translation\TranslationConfig;
use DCarbone\PHPFHIRGenerated\Translation\TranslationContext;
use DCarbone\PHPFHIRGenerated\Translation\TranslationNoticeCollector;
use DCarbone\PHPFHIRGenerated\Translation\TypeTranslatorDispatcher;
use DCarbone\PHPFHIRGenerated\Translation\XverExtensionUnfolder;

/**
 * Round trips fields through the HL7 xver extensions that carry them across a version that lacks them.
 */
class XverExtensionUnfoldingTest extends AbstractTranslationIntegrationTestCase
{
    private const R5_ACCOUNT_JSON = '{"resourceType":"Account","id":"a1","status":"active","name":"Main",'
        . '"currency":{"coding":[{"system":"urn:iso:std:iso:4217","code":"USD"}]},'
        . '"balance":[{"aggregate":{"coding":[{"system":"http://example.org","code":"patient"}]},'
        . '"term":{"coding":[{"system":"http://example.org","code":"long"}]},'
        . '"estimate":true,"amount":{"value":12.5,"currency":"USD"}}]}';

    private static function roundTrip(string $json, string $from, string $via): array
    {
        $translator = new Translator();
        $carried = $translator->translate(self::parse($from, $json), self::version($via));
        $restored = $translator->translate($carried->getResource(), self::version($from));

        return self::decode($restored->getResource());
    }

    public function testASimpleExtensionIsRestoredToItsField(): void
    {
        $account = self::roundTrip(self::R5_ACCOUNT_JSON, 'R5', 'R4');

        self::assertSame(
            ['coding' => [['system' => 'urn:iso:std:iso:4217', 'code' => 'USD']]],
            $account['currency'],
        );
    }

    public function testAComplexExtensionIsRestoredToItsNestedField(): void
    {
        $account = self::roundTrip(self::R5_ACCOUNT_JSON, 'R5', 'R4');

        self::assertCount(1, $account['balance']);
        $balance = $account['balance'][0];
        self::assertSame(true, $balance['estimate']);
        self::assertSame('patient', $balance['aggregate']['coding'][0]['code']);
        self::assertSame('long', $balance['term']['coding'][0]['code']);
        self::assertSame(['value' => 12.5, 'currency' => 'USD'], $balance['amount']);
    }

    public function testARoundTripLeavesNoExtensionsBehind(): void
    {
        $account = self::roundTrip(self::R5_ACCOUNT_JSON, 'R5', 'R4');

        self::assertArrayNotHasKey('extension', $account);
        self::assertSame('a1', $account['id']);
        self::assertSame('active', $account['status']);
        self::assertSame('Main', $account['name']);
    }

    public function testARoundTripReproducesTheOriginalResource(): void
    {
        $original = self::decode(self::parse('R5', self::R5_ACCOUNT_JSON));

        self::assertEquals($original, self::roundTrip(self::R5_ACCOUNT_JSON, 'R5', 'R4'));
    }

    public function testAnEnumBoundCodeIsRestored(): void
    {
        $json = '{"resourceType":"Coverage","id":"c1","status":"active","kind":"insurance",'
            . '"beneficiary":{"reference":"Patient/1"}}';

        $carried = (new Translator())->translate(self::parse('R5', $json), self::version('R4'));
        $extensions = self::decode($carried->getResource())['extension'];
        self::assertSame('http://hl7.org/fhir/5.0/StructureDefinition/extension-Coverage.kind', $extensions[0]['url']);
        self::assertSame('insurance', $extensions[0]['valueCode']);

        $coverage = self::roundTrip($json, 'R5', 'R4');
        self::assertSame('insurance', $coverage['kind']);
        self::assertArrayNotHasKey('extension', $coverage);
    }

    public function testExtensionsUnrelatedToTheHopStayInPlace(): void
    {
        $json = '{"resourceType":"Account","id":"a1","status":"active","currency":{"text":"USD"},'
            . '"extension":[{"url":"http://example.org/other","valueString":"keep"}]}';

        $account = self::roundTrip($json, 'R5', 'R4');

        self::assertSame('USD', $account['currency']['text']);
        self::assertEquals(
            [['url' => 'http://example.org/other', 'valueString' => 'keep']],
            $account['extension'],
        );
    }

    public function testAnExtensionWithAnUnexpectedValueTypeIsLeftAlone(): void
    {
        $json = '{"resourceType":"Account","id":"a1","status":"active",'
            . '"extension":[{"url":"http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency",'
            . '"valueString":"USD"}]}';

        $account = self::decode(
            (new Translator())->translate(self::parse('R4', $json), self::version('R5'))->getResource(),
        );

        self::assertArrayNotHasKey('currency', $account);
        self::assertCount(1, $account['extension']);
    }

    public function testAnExtensionDoesNotOverwriteAnAlreadyPopulatedField(): void
    {
        $json = '{"resourceType":"Account","id":"a1","status":"active","currency":{"text":"EUR"},'
            . '"extension":[{"url":"http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency",'
            . '"valueCodeableConcept":{"text":"USD"}}]}';
        $target = self::parse('R5', $json);
        $metadataFactory = new FieldMetadataFactory();
        $sourceVersion = self::version('R4');
        $targetVersion = self::version('R5');
        $context = new TranslationContext(
            $sourceVersion,
            $targetVersion,
            new TranslationConfig(),
            'Account',
            new TypeTranslatorDispatcher($metadataFactory),
            new TranslationNoticeCollector(),
            HopMapRegistry::getMap($targetVersion, $sourceVersion),
        );

        self::assertFalse(
            (new XverExtensionUnfolder($metadataFactory))->unfold($target, $metadataFactory->forType($target), $context),
        );
        $account = self::decode($target);
        self::assertSame('EUR', $account['currency']['text']);
        self::assertCount(1, $account['extension']);
        self::assertSame('USD', $account['extension'][0]['valueCodeableConcept']['text']);
    }

    public function testAnExtensionCarryingExtraContentIsLeftAlone(): void
    {
        $json = '{"resourceType":"Account","id":"a1","status":"active",'
            . '"extension":[{"url":"http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency",'
            . '"id":"e1","valueCodeableConcept":{"text":"USD"}}]}';

        $account = self::decode(
            (new Translator())->translate(self::parse('R4', $json), self::version('R5'))->getResource(),
        );

        self::assertArrayNotHasKey('currency', $account);
        self::assertSame('e1', $account['extension'][0]['id']);
    }

    public function testAnExtensionFromAnotherReleaseIsLeftAlone(): void
    {
        $json = '{"resourceType":"Account","id":"a1","status":"active",'
            . '"extension":[{"url":"http://hl7.org/fhir/4.3/StructureDefinition/extension-Account.currency",'
            . '"valueCodeableConcept":{"text":"USD"}}]}';

        $account = self::decode(
            (new Translator())->translate(self::parse('R4', $json), self::version('R5'))->getResource(),
        );

        self::assertArrayNotHasKey('currency', $account);
        self::assertCount(1, $account['extension']);
    }

    public function testEachHopRestoresFromItsOwnPackage(): void
    {
        $json = '{"resourceType":"Account","id":"a1","status":"active",'
            . '"active":{"start":"2020-01-01","end":"2020-12-31"}}';

        $account = self::roundTrip($json, 'STU3', 'R4');

        self::assertSame(['start' => '2020-01-01', 'end' => '2020-12-31'], $account['active']);
        self::assertArrayNotHasKey('extension', $account);
    }

    public function testDecimalValuesRemainJsonNumbersWhenTranslated(): void
    {
        $json = '{"resourceType":"Observation","status":"final","code":{"text":"result"},'
            . '"valueQuantity":{"value":12.5}}';

        $translated = (new Translator())->translate(self::parse('R5', $json), self::version('R4'));
        $value = self::decode($translated->getResource())['valueQuantity']['value'];

        self::assertSame(12.5, $value);
    }
}
