<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Translation;

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

use DCarbone\PHPFHIR\Enum\TranslationFieldRuleEnum;

final class HopMapDefinition
{
    /**
     * @param array<string, array<string, \DCarbone\PHPFHIR\Enum\TranslationFieldRuleEnum>> $fieldRules FHIR type name => field name => rule
     * @param array<string, string> $typeRenames Source FHIR type name => target FHIR type name
     * @param string[] $removedTypes
     * @param \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] $codeMaps
     */
    public function __construct(
        private readonly array $fieldRules,
        private readonly array $typeRenames,
        private readonly array $removedTypes,
        private readonly array $codeMaps = [],
    ) {
        foreach ($fieldRules as $typeName => $fields) {
            foreach ($fields as $fieldName => $rule) {
                if (!$rule instanceof TranslationFieldRuleEnum) {
                    throw new \InvalidArgumentException(sprintf(
                        'Field rule for "%s.%s" must be a %s, %s given.',
                        $typeName,
                        $fieldName,
                        TranslationFieldRuleEnum::class,
                        get_debug_type($rule),
                    ));
                }
            }
        }
    }

    /** @return array<string, array<string, \DCarbone\PHPFHIR\Enum\TranslationFieldRuleEnum>> */
    public function getFieldRules(): array
    {
        return $this->fieldRules;
    }

    /** @return array<string, string> */
    public function getTypeRenames(): array
    {
        return $this->typeRenames;
    }

    /** @return string[] */
    public function getRemovedTypes(): array
    {
        return $this->removedTypes;
    }

    /** @return \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] */
    public function getCodeMaps(): array
    {
        return $this->codeMaps;
    }
}
