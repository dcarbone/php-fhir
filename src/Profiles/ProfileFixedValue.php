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

use DCarbone\PHPFHIR\Enum\FixedValueKindEnum;

/**
 * A fixed[x] or pattern[x] value declared on an element.
 */
class ProfileFixedValue
{
    private readonly FixedValueKindEnum $kind;
    private readonly string $type;
    private readonly mixed $value;

    /**
     * @param \DCarbone\PHPFHIR\Enum\FixedValueKindEnum $kind
     * @param string $type FHIR type of the value, e.g. "Uri" or "CodeableConcept".
     * @param mixed $value The decoded JSON value.
     */
    public function __construct(
        FixedValueKindEnum $kind,
        string $type,
        mixed $value,
    ) {
        $this->kind = $kind;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Locates a fixed[x] / pattern[x] value in a raw ElementDefinition, or returns null if there is none.
     */
    public static function fromElementArray(array $element): null|self
    {
        foreach ($element as $key => $value) {
            if (1 === preg_match('{^(fixed|pattern)([A-Z][A-Za-z0-9]*)$}', (string)$key, $m)) {
                return new self(FixedValueKindEnum::from($m[1]), $m[2], $value);
            }
        }
        return null;
    }

    public function getKind(): FixedValueKindEnum
    {
        return $this->kind;
    }

    public function isFixed(): bool
    {
        return FixedValueKindEnum::FIXED === $this->kind;
    }

    public function isPattern(): bool
    {
        return FixedValueKindEnum::PATTERN === $this->kind;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
