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
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;

final class TranslationPackageReader
{
    /**
     * Reads ConceptMap groups that map a source code system to a target code system.
     *
     * Only a single `equivalent` target is usable for a scalar FHIR code. Missing, ambiguous, or
     * non-equivalent targets are retained as null mappings so runtime translation can apply policy.
     *
     * @return \DCarbone\PHPFHIR\Translation\CodeMapDefinition[]
     */
    public function readCodeMaps(TranslationPackageConfig $config): array
    {
        $resources = ProfilePackageReader::readResources($config->getSource(), 'ConceptMap');
        $grouped = [];
        foreach ($resources as $resource) {
            $groups = $resource['group'] ?? [];
            if (!is_array($groups)) {
                throw new \UnexpectedValueException(sprintf(
                    'ConceptMap "%s" has a non-array "group" value.',
                    $resource['id'] ?? '(unknown)',
                ));
            }
            foreach ($groups as $group) {
                if (!is_array($group)
                    || !isset($group['source'], $group['target'])
                    || !is_string($group['source'])
                    || !is_string($group['target'])
                    || '' === $group['source']
                    || '' === $group['target']) {
                    continue;
                }

                $elements = $group['element'] ?? [];
                if (!is_array($elements)) {
                    throw new \UnexpectedValueException(sprintf(
                        'ConceptMap "%s" has a non-array group "element" value.',
                        $resource['id'] ?? '(unknown)',
                    ));
                }
                foreach ($elements as $element) {
                    if (!is_array($element) || !isset($element['code']) || !is_string($element['code'])) {
                        throw new \UnexpectedValueException(sprintf(
                            'ConceptMap "%s" contains an element without a string source code.',
                            $resource['id'] ?? '(unknown)',
                        ));
                    }

                    $equivalentTargets = [];
                    $targets = $element['target'] ?? [];
                    if (!is_array($targets)) {
                        throw new \UnexpectedValueException(sprintf(
                            'ConceptMap "%s" contains a non-array target list.',
                            $resource['id'] ?? '(unknown)',
                        ));
                    }
                    foreach ($targets as $target) {
                        if (!is_array($target) || !isset($target['code']) || !is_string($target['code'])) {
                            continue;
                        }
                        $relationship = $target['relationship'] ?? $target['equivalence'] ?? null;
                        if ('equivalent' === $relationship) {
                            $equivalentTargets[$target['code']] = true;
                        }
                    }
                    $targetCodes = array_keys($equivalentTargets);
                    $targetCode = 1 === count($targetCodes) ? $targetCodes[0] : null;

                    $sourceSystem = $group['source'];
                    $targetSystem = $group['target'];
                    $existing = $grouped[$sourceSystem][$targetSystem][$element['code']] ?? null;
                    if (array_key_exists($element['code'], $grouped[$sourceSystem][$targetSystem] ?? [])) {
                        if ($existing !== $targetCode) {
                            $grouped[$sourceSystem][$targetSystem][$element['code']] = null;
                        }
                    } else {
                        $grouped[$sourceSystem][$targetSystem][$element['code']] = $targetCode;
                    }
                }
            }
        }

        $definitions = [];
        foreach ($grouped as $sourceSystem => $targetSystems) {
            foreach ($targetSystems as $targetSystem => $codeMappings) {
                ksort($codeMappings);
                $definitions[] = new CodeMapDefinition($sourceSystem, $targetSystem, $codeMappings);
            }
        }
        return $definitions;
    }
}
