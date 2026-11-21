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
use DCarbone\PHPFHIRGenerated\Translation\UnmappableFieldException;

/**
 * Exercises each OnUnmappableFieldEnum policy against a field that cannot be represented in the target version.
 */
class UnmappableFieldPolicyTest extends AbstractTranslationIntegrationTestCase
{
    private const GENDER_ONLY_PATIENT_JSON = '{"resourceType":"Patient","id":"p1","gender":"female"}';

    public function testThrowPolicyRaisesUnmappableFieldException(): void
    {
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW));

        $this->expectException(UnmappableFieldException::class);
        $translator->translate(self::parse('R4', self::GENDER_ONLY_PATIENT_JSON), self::version('DSTU1'));
    }

    public function testExtensionPolicyRequiresAnOfficialXverDefinition(): void
    {
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::EXTENSION));

        $this->expectException(MissingXverDefinitionException::class);
        $this->expectExceptionMessage('Patient.gender.value');
        $translator->translate(self::parse('R4', self::GENDER_ONLY_PATIENT_JSON), self::version('DSTU1'));
    }

    public function testDropPolicyKeepsTranslatableFields(): void
    {
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));

        $result = $translator->translate(self::parse('R4', self::GENDER_ONLY_PATIENT_JSON), self::version('DSTU1'));

        self::assertEquals(['resourceType' => 'Patient', 'id' => 'p1'], self::decode($result->getResource()));
        self::assertNotSame([], $result->getNotices());
    }
}
