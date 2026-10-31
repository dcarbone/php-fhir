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
    private ProfilePackageMetadata $_metadata;

    /** @var array<string, array<string, ProfilePackedResource>> resource type => id => packed resource */
    private array $_resources = [];
    /** @var array<string, true> ids of StructureDefinitions whose derivation is not "constraint" */
    private array $_nonProfileSDs = [];
    /** @var array<string, array<string, string>> resource type => canonical url => id */
    private array $_urlIndex = [];

    /** @var array<string, ProfilePackage> */
    private array $_loadedDependencies = [];

    public function __construct(ProfilePackageConfig $config, array $manifest)
    {
        $this->_config = $config;
        $this->_metadata = ProfilePackageMetadata::fromManifest($manifest);
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

    public function getMetadata(): ProfilePackageMetadata
    {
        return $this->_metadata;
    }

    public function getName(): string
    {
        return $this->_metadata->getName();
    }

    public function getVersion(): string
    {
        return $this->_metadata->getVersion();
    }

    public function getCanonical(): null|string
    {
        return $this->_metadata->getCanonical();
    }

    /**
     * @return string[] FHIR versions this package declares support for.
     */
    public function getFHIRVersions(): array
    {
        return $this->_metadata->getFHIRVersions();
    }

    /**
     * @return array<string, string> Declared dependencies as package name => version.
     */
    public function getDeclaredDependencies(): array
    {
        return $this->_metadata->getDeclaredDependencies();
    }

    /**
     * @internal Used by the reader while populating the package.
     */
    public function addResource(array $resource): void
    {
        $packed = ProfilePackedResource::fromResource($resource);
        if (null !== $packed) {
            $this->addPackedResource($packed);
        }
    }

    /**
     * @internal Used by the reader while populating the package.
     */
    public function addPackedResource(ProfilePackedResource $packed): void
    {
        $type = $packed->getType()->value;
        $this->_resources[$type][$packed->getId()] = $packed;
        if (null !== $packed->getUrl()) {
            $this->_urlIndex[$type][$packed->getUrl()] = $packed->getId();
        }
        if (ProfileResourceTypeEnum::STRUCTURE_DEFINITION === $packed->getType()) {
            // Recorded so profiles can be listed without inflating every base / specialization definition.
            if ($packed->isConstraint()) {
                unset($this->_nonProfileSDs[$packed->getId()]);
            } else {
                $this->_nonProfileSDs[$packed->getId()] = true;
            }
        }
    }

    /**
     * @internal Used by the reader while populating the package.
     */
    public function addLoadedDependency(ProfilePackage $package): void
    {
        $this->_loadedDependencies[$package->getName()] = $package;
    }

    /**
     * @return array<string, ProfilePackage>
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
        $out = [];
        foreach ($this->_resources[ProfileResourceTypeEnum::STRUCTURE_DEFINITION->value] as $id => $packed) {
            if (isset($this->_nonProfileSDs[$id])) {
                continue;
            }
            $out[$id] = $packed->unpack();
        }
        return $out;
    }

    /**
     * @return array<string, array>
     */
    public function getValueSets(): array
    {
        $out = [];
        foreach ($this->_resources[ProfileResourceTypeEnum::VALUE_SET->value] as $id => $packed) {
            $out[$id] = $packed->unpack();
        }
        return $out;
    }

    /**
     * @return array<string, array>
     */
    public function getCodeSystems(): array
    {
        $out = [];
        foreach ($this->_resources[ProfileResourceTypeEnum::CODE_SYSTEM->value] as $id => $packed) {
            $out[$id] = $packed->unpack();
        }
        return $out;
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

    /**
     * @param array<string, bool> $visited Names of the packages already searched, as dependencies are shared.
     */
    private function _resolve(ProfileResourceTypeEnum $type, string $canonical, array &$visited = []): null|array
    {
        $visited[$this->getName()] = true;
        $url = self::stripCanonicalVersion($canonical);
        if (isset($this->_urlIndex[$type->value][$url])) {
            return $this->_resources[$type->value][$this->_urlIndex[$type->value][$url]]->unpack();
        }
        foreach ($this->_loadedDependencies as $name => $dependency) {
            if (!isset($visited[$name]) && null !== ($found = $dependency->_resolve($type, $canonical, $visited))) {
                return $found;
            }
        }
        return null;
    }
}
