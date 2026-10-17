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
 * Slicing rules declared on a repeating element.
 */
class ProfileSlicing
{
    private readonly array $discriminators;
    private readonly string $rules;
    private readonly bool $ordered;
    private readonly null|string $description;

    /**
     * @param array<array{type: string, path: string}> $discriminators
     */
    public function __construct(
        array $discriminators,
        string $rules,
        bool $ordered = false,
        null|string $description = null,
    ) {
        $this->discriminators = $discriminators;
        $this->rules = $rules;
        $this->ordered = $ordered;
        $this->description = $description;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['rules']) || !is_string($data['rules'])) {
            throw new ProfileDefinitionException('Slicing is missing required string field "rules".');
        }
        $discriminators = [];
        foreach ($data['discriminator'] ?? [] as $d) {
            if (!is_array($d) || !isset($d['type'], $d['path']) || !is_string($d['type']) || !is_string($d['path'])) {
                throw new ProfileDefinitionException('Slicing discriminator must have string "type" and "path".');
            }
            $discriminators[] = ['type' => $d['type'], 'path' => $d['path']];
        }
        return new self($discriminators, $data['rules'], (bool)($data['ordered'] ?? false), $data['description'] ?? null);
    }

    /** @return array<array{type: string, path: string}> */
    public function getDiscriminators(): array
    {
        return $this->discriminators;
    }

    /** @return string "closed", "open" or "openAtEnd" */
    public function getRules(): string
    {
        return $this->rules;
    }

    public function isOrdered(): bool
    {
        return $this->ordered;
    }

    public function getDescription(): null|string
    {
        return $this->description;
    }
}
