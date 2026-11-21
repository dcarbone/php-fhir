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
use DCarbone\PHPFHIR\Version\Definition\Types;

final class HopMapBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] $codeMaps
     * @param array<string, \DCarbone\PHPFHIR\Translation\XverExtensionDefinition> $xverExtensions
     */
    public function build(Types $source, Types $target, array $codeMaps = [], array $xverExtensions = []): HopMapDefinition
    {
        $targetTypes = [];
        foreach ($target->getIterator() as $type) {
            $targetTypes[$type->getFHIRName()] = true;
        }

        $removedTypes = [];
        foreach ($source->getIterator() as $sourceType) {
            $typeName = $sourceType->getFHIRName();
            // Value-set enumerations are named per release (e.g. "X-list" vs "XEnum"), so a name mismatch
            // does not mean the type was removed; the field declaring it carries the real type.
            if (!isset($targetTypes[$typeName]) && !$sourceType->isEnumerated()) {
                $removedTypes[] = $typeName;
            }
        }
        sort($removedTypes, SORT_NATURAL | SORT_FLAG_CASE);

        return new HopMapDefinition($removedTypes, $codeMaps, $xverExtensions);
    }

    public function getMapClassName(Version $source, Version $target): string
    {
        return sprintf('HopMap_%sTo%s', $source->getConstName(), $target->getConstName());
    }

    /**
     * @param \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] $codeMaps
     * @param array<string, \DCarbone\PHPFHIR\Translation\XverExtensionDefinition> $xverExtensions
     */
    public function renderMap(Version $source, Version $target, array $codeMaps = [], array $xverExtensions = []): string
    {
        return $this->renderMapDefinition(
            $source,
            $target,
            $this->build(
                $source->getDefinition()->getTypes(),
                $target->getDefinition()->getTypes(),
                $codeMaps,
                $xverExtensions,
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
}
