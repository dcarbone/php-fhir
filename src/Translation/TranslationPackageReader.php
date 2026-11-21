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

use DCarbone\PHPFHIR\Config\TranslationPackageConfig;
use DCarbone\PHPFHIR\Enum\ProfileResourceTypeEnum;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;

final class TranslationPackageReader
{
    /**
     * Reads ConceptMap groups that map a source code system to a target code system.
     *
     * Only a single unconditional `equivalent` or R4 `equal` target is usable. Missing, ambiguous,
     * or unsupported targets remain null mappings so runtime translation can apply policy.
     *
     * @return \DCarbone\PHPFHIR\Translation\CodeMapDefinition[]
     * @throws ProfilePackageException
     * @throws ProfileDefinitionException
     */
    public function readCodeMaps(TranslationPackageConfig $config): array
    {
        $package = ProfilePackageReader::readSource($config->getSource(), ProfileResourceTypeEnum::CONCEPT_MAP);
        /** @var array<string, CodeMapDefinition> $definitions */
        $definitions = [];
        foreach ($package->getConceptMaps() as $conceptMap) {
            foreach ($conceptMap->groups as $group) {
                $codeMappings = [];
                foreach ($group->elements as $element) {
                    $targetCode = 1 === count($element->equivalentTargets) ? $element->equivalentTargets[0] : null;
                    if (!array_key_exists($element->code, $codeMappings)) {
                        $codeMappings[$element->code] = $targetCode;
                    } elseif ($codeMappings[$element->code] !== $targetCode) {
                        $codeMappings[$element->code] = null;
                    }
                }
                if ([] === $codeMappings) {
                    continue;
                }
                ksort($codeMappings, SORT_STRING);
                $definition = new CodeMapDefinition(
                    $group->source,
                    $group->target,
                    $codeMappings,
                    $group->sourceVersion,
                    $group->targetVersion,
                );
                $key = $definition->getGroupKey();
                $definitions[$key] = isset($definitions[$key]) ? $definitions[$key]->merge($definition) : $definition;
            }
        }
        return array_values($definitions);
    }
}
