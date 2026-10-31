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

/**
 * A slice of a repeating element: how its members are recognised, and how many there may be.
 */
class ProfileSliceRule
{
    private readonly string $path;
    private readonly string $name;
    private readonly array $match;
    private readonly null|int $min;
    private readonly null|int $max;

    /**
     * @param string $path Path of the sliced element relative to the profiled type.
     * @param string $name Slice name.
     * @param array<string, mixed> $match Values, keyed by field, an item must carry to belong to the slice.
     */
    public function __construct(
        string $path,
        string $name,
        array $match,
        null|int $min = null,
        null|int $max = null,
    ) {
        $this->path = $path;
        $this->name = $name;
        $this->match = $match;
        $this->min = $min;
        $this->max = $max;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<string, mixed> */
    public function getMatch(): array
    {
        return $this->match;
    }

    public function getMin(): null|int
    {
        return $this->min;
    }

    public function getMax(): null|int
    {
        return $this->max;
    }
}
