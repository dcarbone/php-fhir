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

use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;

final class HopMapBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] $codeMaps
     */
    public function build(Types $source, Types $target, array $codeMaps = []): HopMapDefinition
    {
        $targetTypes = [];
        foreach ($target->getIterator() as $type) {
            $targetTypes[$type->getFHIRName()] = $type;
        }

        $fieldRules = [];
        $removedTypes = [];
        foreach ($source->getIterator() as $sourceType) {
            $typeName = $sourceType->getFHIRName();
            if (!isset($targetTypes[$typeName])) {
                $removedTypes[] = $typeName;
                continue;
            }

            $targetFields = $this->getPropertiesByName($targetTypes[$typeName]);
            foreach ($this->getPropertiesByName($sourceType) as $fieldName => $_property) {
                if (!isset($targetFields[$fieldName])) {
                    $fieldRules[$typeName][$fieldName] = 'unmappable';
                }
            }
        }

        ksort($fieldRules);
        foreach ($fieldRules as &$fields) {
            ksort($fields);
        }
        unset($fields);
        sort($removedTypes, SORT_NATURAL | SORT_FLAG_CASE);

        return new HopMapDefinition($fieldRules, [], $removedTypes, $codeMaps);
    }

    public function getMapClassName(Version $source, Version $target): string
    {
        return sprintf('HopMap_%sTo%s', $source->getConstName(), $target->getConstName());
    }

    /** @param \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] $codeMaps */
    public function renderMap(Version $source, Version $target, array $codeMaps = []): string
    {
        return $this->renderMapDefinition(
            $source,
            $target,
            $this->build(
                $source->getDefinition()->getTypes(),
                $target->getDefinition()->getTypes(),
                $codeMaps,
            ),
        );
    }

    public function renderMapDefinition(Version $source, Version $target, HopMapDefinition $definition): string
    {
        return Templates::renderTranslationHopMap(
            $source,
            $target,
            $definition,
            $this->getMapClassName($source, $target),
        );
    }

    /**
     * @return array<string, \DCarbone\PHPFHIR\Version\Definition\Property>
     */
    private function getPropertiesByName(Type $type): array
    {
        $properties = [];
        foreach ($type->getParentPropertiesIterator(true) as $property) {
            $name = $property->getName();
            if (null !== $name && '' !== $name) {
                $properties[$name] = $property;
            }
        }
        foreach ($type->getProperties()->getIterator() as $property) {
            $name = $property->getName();
            if (null !== $name && '' !== $name) {
                $properties[$name] = $property;
            }
        }
        return $properties;
    }
}
