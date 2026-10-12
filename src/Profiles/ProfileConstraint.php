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
 * An invariant declared on an element.  Invariants are metadata only; the FHIRPath expression is never evaluated.
 */
class ProfileConstraint
{
    public function __construct(private readonly string      $key,
                                private readonly string      $severity,
                                private readonly string      $human,
                                private readonly null|string $expression = null)
    {
    }

    public static function fromArray(array $data): self
    {
        foreach (['key', 'severity', 'human'] as $k) {
            if (!isset($data[$k]) || !is_string($data[$k])) {
                throw new ProfileDefinitionException(sprintf('Constraint is missing required string field "%s".', $k));
            }
        }
        return new self($data['key'], $data['severity'], $data['human'], $data['expression'] ?? null);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /** @return string "error" or "warning" */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getHuman(): string
    {
        return $this->human;
    }

    public function getExpression(): null|string
    {
        return $this->expression;
    }
}
