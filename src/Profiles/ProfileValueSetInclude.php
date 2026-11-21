<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * One "compose.include" or "compose.exclude" entry of a ValueSet.
 */
final class ProfileValueSetInclude
{
    /**
     * @param list<string> $valueSets Canonicals of the ValueSets referenced.
     * @param null|list<string> $concepts Codes listed explicitly; null if the entry lists none.
     * @param bool $enumerable False if the entry has a filter, or is malformed, and so cannot be listed from what the
     *                         package holds.
     */
    public function __construct(
        public readonly null|string $system,
        public readonly array $valueSets,
        public readonly null|array $concepts,
        public readonly bool $enumerable,
    ) {
    }

    public static function fromResource(mixed $include): self
    {
        if (!is_array($include)) {
            return new self(null, [], null, false);
        }
        $enumerable = !isset($include['filter']);

        $concepts = null;
        if (isset($include['concept']) && is_array($include['concept'])) {
            $concepts = [];
            foreach ($include['concept'] as $concept) {
                if (!is_array($concept) || !isset($concept['code']) || !is_string($concept['code'])) {
                    $enumerable = false;
                    continue;
                }
                $concepts[] = $concept['code'];
            }
        }

        $valueSets = [];
        $rawValueSets = $include['valueSet'] ?? [];
        if (!is_array($rawValueSets)) {
            $enumerable = false;
            $rawValueSets = [];
        }
        foreach ($rawValueSets as $valueSet) {
            if (is_string($valueSet)) {
                $valueSets[] = $valueSet;
            } else {
                $enumerable = false;
            }
        }

        return new self(
            isset($include['system']) && is_string($include['system']) ? $include['system'] : null,
            $valueSets,
            $concepts,
            $enumerable,
        );
    }
}
