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
 * A terminology binding on an element.
 */
class ProfileBinding
{
    public const STRENGTH_REQUIRED = 'required';
    public const STRENGTH_EXTENSIBLE = 'extensible';
    public const STRENGTH_PREFERRED = 'preferred';
    public const STRENGTH_EXAMPLE = 'example';

    public function __construct(private readonly string      $strength,
                                private readonly null|string $valueSet = null,
                                private readonly null|string $description = null)
    {
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['strength']) || !is_string($data['strength'])) {
            throw new ProfileDefinitionException('Binding is missing required string field "strength".');
        }
        return new self($data['strength'], $data['valueSet'] ?? null, $data['description'] ?? null);
    }

    public function getStrength(): string
    {
        return $this->strength;
    }

    public function isRequired(): bool
    {
        return self::STRENGTH_REQUIRED === $this->strength;
    }

    /** @return null|string Canonical url, possibly with a "|version" suffix. */
    public function getValueSet(): null|string
    {
        return $this->valueSet;
    }

    public function getDescription(): null|string
    {
        return $this->description;
    }
}
