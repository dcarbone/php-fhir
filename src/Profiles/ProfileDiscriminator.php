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
 * A discriminator of a sliced element: how members of its slices are told apart.
 */
class ProfileDiscriminator
{
    private readonly string $type;
    private readonly string $path;

    /**
     * @param string $type "value", "pattern", "type", "profile" or "exists".
     * @param string $path FHIRPath expression, relative to the sliced element.
     */
    public function __construct(string $type, string $path)
    {
        $this->type = $type;
        $this->path = $path;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['type'], $data['path']) || !is_string($data['type']) || !is_string($data['path'])) {
            throw new ProfileDefinitionException('Slicing discriminator must have string "type" and "path".');
        }
        return new self($data['type'], $data['path']);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
