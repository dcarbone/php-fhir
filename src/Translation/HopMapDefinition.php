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

final class HopMapDefinition
{
    /**
     * @param string[] $removedTypes
     * @param \DCarbone\PHPFHIR\Translation\CodeMapDefinition[] $codeMaps
     * @param array<string, \DCarbone\PHPFHIR\Translation\XverExtensionDefinition> $xverExtensions Indexed by
     *        "<source type FHIR name>.<source field>".
     */
    public function __construct(
        private readonly array $removedTypes,
        private readonly array $codeMaps = [],
        private readonly array $xverExtensions = [],
    ) {
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

    /** @return array<string, \DCarbone\PHPFHIR\Translation\XverExtensionDefinition> */
    public function getXverExtensions(): array
    {
        return $this->xverExtensions;
    }
}
