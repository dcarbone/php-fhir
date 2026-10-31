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

use DCarbone\PHPFHIR\Enum\ProfileResourceTypeEnum;

/**
 * A conformance resource held by a package.  Packages can be enormous and resources are rarely read more than once,
 * so the resource itself is kept compressed and only the fields needed to index it are kept at hand.
 */
class ProfilePackedResource
{
    private readonly ProfileResourceTypeEnum $_type;
    private readonly string $_id;
    private readonly null|string $_url;
    private readonly null|string $_derivation;
    private readonly array|string $_data;

    /**
     * @param null|string $derivation The "derivation" of a StructureDefinition, if known without unpacking it.
     * @param array|string $data The resource, either decoded or deflated JSON.
     */
    public function __construct(
        ProfileResourceTypeEnum $type,
        string $id,
        null|string $url,
        null|string $derivation,
        array|string $data,
    ) {
        $this->_type = $type;
        $this->_id = $id;
        $this->_url = $url;
        $this->_derivation = $derivation;
        $this->_data = $data;
    }

    /**
     * Packs a decoded resource.
     *
     * @return null|self Null if the resource is not of a retained type or has no id.
     */
    public static function fromResource(array $resource): null|self
    {
        $type = $resource['resourceType'] ?? null;
        $id = $resource['id'] ?? null;
        if (!is_string($type) || null === ($enum = ProfileResourceTypeEnum::tryFrom($type)) || !is_string($id) || '' === $id) {
            return null;
        }
        $data = $resource;
        if (function_exists('gzdeflate')) {
            $data = gzdeflate(json_encode($resource, JSON_THROW_ON_ERROR), 1);
        }
        return new self(
            $enum,
            $id,
            isset($resource['url']) && is_string($resource['url']) ? $resource['url'] : null,
            isset($resource['derivation']) && is_string($resource['derivation']) ? $resource['derivation'] : null,
            $data,
        );
    }

    public function getType(): ProfileResourceTypeEnum
    {
        return $this->_type;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function getUrl(): null|string
    {
        return $this->_url;
    }

    /**
     * Whether this is a StructureDefinition constraining another, i.e. a profile.  Unpacks the resource if the
     * derivation was not recorded when packing.
     */
    public function isConstraint(): bool
    {
        if (ProfileResourceTypeEnum::STRUCTURE_DEFINITION !== $this->_type) {
            return false;
        }
        return 'constraint' === ($this->_derivation ?? $this->unpack()['derivation'] ?? null);
    }

    /**
     * @return array The decoded resource.
     */
    public function unpack(): array
    {
        return is_string($this->_data)
            ? json_decode(gzinflate($this->_data), true, 512, JSON_THROW_ON_ERROR)
            : $this->_data;
    }
}
