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
 * One permitted type of an element, optionally restricted to specific profiles.
 */
class ProfileElementType
{
    /**
     * @param string[] $profiles Canonical urls of profiles the value must conform to.
     * @param string[] $targetProfiles Canonical urls of profiles the target of a Reference must conform to.
     */
    public function __construct(private readonly string $code,
                                private readonly array  $profiles = [],
                                private readonly array  $targetProfiles = [])
    {
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['code']) || !is_string($data['code'])) {
            throw new ProfileDefinitionException('Element type is missing required string field "code".');
        }
        return new self(
            $data['code'],
            array_values(array_filter((array)($data['profile'] ?? []), 'is_string')),
            array_values(array_filter((array)($data['targetProfile'] ?? []), 'is_string')),
        );
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /** @return string[] */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    /** @return string[] */
    public function getTargetProfiles(): array
    {
        return $this->targetProfiles;
    }
}
