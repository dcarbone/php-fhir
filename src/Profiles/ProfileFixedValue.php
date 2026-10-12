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
 * A fixed[x] or pattern[x] value declared on an element.
 */
class ProfileFixedValue
{
    public const KIND_FIXED = 'fixed';
    public const KIND_PATTERN = 'pattern';

    /**
     * @param string $kind One of the KIND_* constants.
     * @param string $type FHIR type of the value, e.g. "Uri" or "CodeableConcept".
     * @param mixed $value The decoded JSON value.
     */
    public function __construct(private readonly string $kind,
                                private readonly string $type,
                                private readonly mixed  $value)
    {
    }

    /**
     * Locates a fixed[x] / pattern[x] value in a raw ElementDefinition, or returns null if there is none.
     */
    public static function fromElementArray(array $element): null|self
    {
        foreach ($element as $key => $value) {
            if (1 === preg_match('{^(fixed|pattern)([A-Z][A-Za-z0-9]*)$}', (string)$key, $m)) {
                return new self($m[1], $m[2], $value);
            }
        }
        return null;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function isFixed(): bool
    {
        return self::KIND_FIXED === $this->kind;
    }

    public function isPattern(): bool
    {
        return self::KIND_PATTERN === $this->kind;
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
