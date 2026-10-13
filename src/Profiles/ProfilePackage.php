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

use DCarbone\PHPFHIR\Config\ProfilePackageConfig;
use DCarbone\PHPFHIR\Enum\ProfileResourceTypeEnum;

/**
 * An in-memory index of the conformance resources found in a FHIR NPM-style package.
 *
 * Only StructureDefinition, ValueSet and CodeSystem resources are retained.
 */
class ProfilePackage
{
    private ProfilePackageConfig $_config;
    private array $_manifest;

    /** @var array<string, array<string, array>> resource type => id => resource */
    private array $_resources = [];
    /** @var array<string, array<string, string>> resource type => canonical url => id */
    private array $_urlIndex = [];

    /** @var array<string, \DCarbone\PHPFHIR\Profiles\ProfilePackage> */
    private array $_loadedDependencies = [];

    public function __construct(ProfilePackageConfig $config, array $manifest)
    {
        $this->_config = $config;
        $this->_manifest = $manifest;
        foreach (ProfileResourceTypeEnum::cases() as $type) {
            $this->_resources[$type->value] = [];
            $this->_urlIndex[$type->value] = [];
        }
    }

    /**
     * Strips any "|version" suffix from a canonical url.
     */
    public static function stripCanonicalVersion(string $canonical): string
    {
        $pos = strpos($canonical, '|');
        return false === $pos ? $canonical : substr($canonical, 0, $pos);
    }

    public function getConfig(): ProfilePackageConfig
    {
        return $this->_config;
    }

    public function getName(): string
    {
        return $this->_manifest['name'];
    }

    public function getVersion(): string
    {
        return $this->_manifest['version'];
    }

    public function getCanonical(): null|string
    {
        return $this->_manifest['canonical'] ?? null;
    }

    /**
     * @return string[] FHIR versions this package declares support for.
     */
    public function getFHIRVersions(): array
    {
        return array_values((array)($this->_manifest['fhirVersions'] ?? []));
    }

    /**
     * @return array<string, string> Declared dependencies as package name => version.
     */
    public function getDeclaredDependencies(): array
    {
        return (array)($this->_manifest['dependencies'] ?? []);
    }

    public function getManifest(): array
    {
        return $this->_manifest;
    }

    /**
     * @internal Used by the reader while populating the package.
     */
    public function addResource(array $resource): void
    {
        $packed = self::packResource($resource);
        if (null !== $packed) {
            $this->addPackedResource($packed);
        }
    }

    /**
     * Prepares a resource for storage.  Packages can be enormous and resources are rarely read more than once, so
     * they are kept compressed.
     *
     * @return null|array{type: string, id: string, url: null|string, data: array|string} Null if the resource is not retained.
     * @internal Used by the reader while populating the package.
     */
    public static function packResource(array $resource): null|array
    {
        $type = $resource['resourceType'] ?? null;
        $id = $resource['id'] ?? null;
        if (!is_string($type) || null === ProfileResourceTypeEnum::tryFrom($type) || !is_string($id) || '' === $id) {
            return null;
        }
        $data = $resource;
        if (function_exists('gzdeflate')) {
            $data = gzdeflate(json_encode($resource, JSON_THROW_ON_ERROR), 1);
        }
        return [
            'type' => $type,
            'id' => $id,
            'url' => isset($resource['url']) && is_string($resource['url']) ? $resource['url'] : null,
            'data' => $data,
        ];
    }

    /**
     * @param array{type: string, id: string, url: null|string, data: array|string} $packed
     * @internal Used by the reader while populating the package.
     */
    public function addPackedResource(array $packed): void
    {
        $this->_resources[$packed['type']][$packed['id']] = $packed['data'];
        if (null !== $packed['url']) {
            $this->_urlIndex[$packed['type']][$packed['url']] = $packed['id'];
        }
    }

    private static function _unpack(array|string $data): array
    {
        return is_string($data) ? json_decode(gzinflate($data), true, 512, JSON_THROW_ON_ERROR) : $data;
    }

    /**
     * @internal Used by the reader while populating the package.
     */
    public function addLoadedDependency(ProfilePackage $package): void
    {
        $this->_loadedDependencies[$package->getName()] = $package;
    }

    /**
     * @return array<string, \DCarbone\PHPFHIR\Profiles\ProfilePackage>
     */
    public function getLoadedDependencies(): array
    {
        return $this->_loadedDependencies;
    }

    /**
     * Returns all profiles (StructureDefinitions with a "constraint" derivation) in this package, indexed by id.
     *
     * @return array<string, array>
     */
    public function getStructureDefinitions(): array
    {
        return array_filter(
            array_map(self::_unpack(...), $this->_resources[ProfileResourceTypeEnum::STRUCTURE_DEFINITION->value]),
            static fn(array $sd): bool => 'constraint' === ($sd['derivation'] ?? null),
        );
    }

    /**
     * @return array<string, array>
     */
    public function getValueSets(): array
    {
        return array_map(self::_unpack(...), $this->_resources[ProfileResourceTypeEnum::VALUE_SET->value]);
    }

    /**
     * @return array<string, array>
     */
    public function getCodeSystems(): array
    {
        return array_map(self::_unpack(...), $this->_resources[ProfileResourceTypeEnum::CODE_SYSTEM->value]);
    }

    /**
     * Locates a StructureDefinition by canonical url (optionally "|version" suffixed) in this package or, failing
     * that, in any loaded dependency.
     */
    public function resolveStructureDefinition(string $canonical): null|array
    {
        return $this->_resolve(ProfileResourceTypeEnum::STRUCTURE_DEFINITION, $canonical);
    }

    public function resolveValueSet(string $canonical): null|array
    {
        return $this->_resolve(ProfileResourceTypeEnum::VALUE_SET, $canonical);
    }

    public function resolveCodeSystem(string $canonical): null|array
    {
        return $this->_resolve(ProfileResourceTypeEnum::CODE_SYSTEM, $canonical);
    }

    private function _resolve(ProfileResourceTypeEnum $type, string $canonical): null|array
    {
        $url = self::stripCanonicalVersion($canonical);
        if (isset($this->_urlIndex[$type->value][$url])) {
            return self::_unpack($this->_resources[$type->value][$this->_urlIndex[$type->value][$url]]);
        }
        foreach ($this->_loadedDependencies as $dependency) {
            if (null !== ($found = $dependency->_resolve($type, $canonical))) {
                return $found;
            }
        }
        return null;
    }
}
