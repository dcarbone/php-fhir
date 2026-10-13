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

use DCarbone\PHPFHIR\Enum\BindingStrengthEnum;

/**
 * A terminology binding on an element.
 */
class ProfileBinding
{
    private readonly BindingStrengthEnum $strength;
    private readonly null|string $valueSet;
    private readonly null|string $description;

    public function __construct(
        BindingStrengthEnum $strength,
        null|string $valueSet = null,
        null|string $description = null,
    ) {
        $this->strength = $strength;
        $this->valueSet = $valueSet;
        $this->description = $description;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['strength']) || !is_string($data['strength'])) {
            throw new ProfileDefinitionException('Binding is missing required string field "strength".');
        }
        $strength = BindingStrengthEnum::tryFrom($data['strength']);
        if (null === $strength) {
            throw new ProfileDefinitionException(sprintf('Binding has unknown strength "%s".', $data['strength']));
        }
        return new self($strength, $data['valueSet'] ?? null, $data['description'] ?? null);
    }

    public function getStrength(): BindingStrengthEnum
    {
        return $this->strength;
    }

    public function isRequired(): bool
    {
        return BindingStrengthEnum::REQUIRED === $this->strength;
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
