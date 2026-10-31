<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles\Rules;

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

use DCarbone\PHPFHIR\Profiles\ProfileFixedValue;

/**
 * Constraints a profile places on one element, as evaluated by the generated profile class.
 */
class ProfileElementRule
{
    private readonly string $path;
    private readonly null|int $min;
    private readonly null|int $max;
    private readonly array $types;
    private readonly null|ProfileFixedValue $fixedValue;
    private readonly null|array $codes;

    /**
     * @param string $path Element path relative to the profiled type, e.g. "name.family" or "deceased[x]".
     * @param null|int $min Minimum cardinality, if the profile raises it above zero.
     * @param null|int $max Maximum cardinality, if the profile bounds it.
     * @param string[] $types Type codes a choice element is restricted to.
     * @param null|array<int, array{0: string, 1: string}> $codes [system, code] pairs of a required binding.
     */
    public function __construct(
        string $path,
        null|int $min = null,
        null|int $max = null,
        array $types = [],
        null|ProfileFixedValue $fixedValue = null,
        null|array $codes = null,
    ) {
        $this->path = $path;
        $this->min = $min;
        $this->max = $max;
        $this->types = $types;
        $this->fixedValue = $fixedValue;
        $this->codes = $codes;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMin(): null|int
    {
        return $this->min;
    }

    public function getMax(): null|int
    {
        return $this->max;
    }

    /** @return string[] */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function hasTypes(): bool
    {
        return [] !== $this->types;
    }

    public function getFixedValue(): null|ProfileFixedValue
    {
        return $this->fixedValue;
    }

    /** @return null|array<int, array{0: string, 1: string}> */
    public function getCodes(): null|array
    {
        return $this->codes;
    }

    /** Whether the rule constrains anything at all. */
    public function isEmpty(): bool
    {
        return null === $this->min
            && null === $this->max
            && [] === $this->types
            && null === $this->fixedValue
            && null === $this->codes;
    }
}
