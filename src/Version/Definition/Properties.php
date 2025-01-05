<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use Countable;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Version;

/**
 * Class Properties
 * @package DCarbone\PHPFHIR\Version\Definition\Type
 */
class Properties implements Countable
{
    /** @var \DCarbone\PHPFHIR\Version\Definition\Property[] */
    private array $properties = [];
    /** @var \DCarbone\PHPFHIR\Version\Definition\Property[] */
    private array $_sortedProperties;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Property[] */
    private array $_localProperties;
    /** @var \DCarbone\PHPFHIR\Version\Definition\Property[] */
    private array $_localSortedProperties;

    /** @var bool */
    private bool $cacheBuilt = false;

    /** @var \DCarbone\PHPFHIR\Config */
    private Config $config;

    /** @var \DCarbone\PHPFHIR\Version */
    private Version $version;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Type */
    private Type $type;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     */
    public function __construct(Config $config, Version $version, Type $type)
    {
        $this->config = $config;
        $this->version = $version;
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['properties' => $this->properties];
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @return \DCarbone\PHPFHIR\Version\Definition\Properties
     */
    public function addProperty(Property &$property): Properties
    {
        $pname = $property->getName();
        $pref = $property->getRef();
        if (null === $pname && null === $pref) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot add Property to Type "%s" as it has no $name or $ref defined',
                    $this->getType()->getFHIRName()
                )
            );
        }
        foreach ($this->properties as $current) {
            if ($property === $current) {
                return $this;
            }
            $cname = $current->getName();
            $cref = $current->getRef();
            if (null !== $pname && null !== $cname && $pname === $cname) {
                $this->config->getLogger()->notice(
                    sprintf(
                        'Type "%s" already has Property "%s" (name), probably some duplicate definition nonsense. Keeping original.',
                        $this->getType()->getFHIRName(),
                        $property->getName()
                    )
                );
                $property = $current;
                return $this;
            } elseif (null !== $pref && null !== $cref && $cref === $pref) {
                $this->config->getLogger()->notice(
                    sprintf(
                        'Type "%s" already has Property "%s" (ref), probably some duplicate definition nonsense. Keeping original.',
                        $this->getType()->getFHIRName(),
                        $property->getRef()
                    )
                );
                $property = $current;
                return $this;
            }
        }
        $this->properties[] = $property;
        $this->cacheBuilt = false;
        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Version\Definition\Property|null
     */
    public function getProperty(string $name): ?Property
    {
        foreach ($this->properties as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name): bool
    {
        return null !== $this->getProperty($name);
    }

    /**
     * Returns an iterator containing all properties, including those inherited from parent types
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getAllPropertiesIterator(): iterable
    {
        return new \ArrayIterator($this->properties);
    }

    /**
     * Returns an iterator contanining all properties, including those inherited from parent types, sorted ascending by name
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getAllSortedPropertiesIterator(): iterable
    {
        $this->_buildLocalCaches();
        return new \ArrayIterator($this->_sortedProperties);
    }

    /**
     * Returns an indexed iterator containing only properties local to this type.
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getIndexedLocalPropertiesIterator(): iterable
    {
        $this->_buildLocalCaches();
        return \SplFixedArray::fromArray($this->_localProperties, preserveKeys: false);
    }

    /**
     * Returns an iterator containing only properties local to this type.
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getLocalPropertiesIterator(): iterable
    {
        $this->_buildLocalCaches();
        return new \ArrayIterator($this->_localProperties);
    }

    /**
     * @return \Generator<\DCarbone\PHPFHIR\Version\Definition\Property>
     */
    public function getLocalPropertiesGenerator(): \Generator
    {
        $this->_buildLocalCaches();
        foreach($this->_localProperties as $p) {
            yield $p;
        }
    }

    /**
     * Returns an iterator containing only properties local to this type, sorted ascending by name
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getLocalSortedPropertiesIterator(): iterable
    {
        $this->_buildLocalCaches();
        return new \ArrayIterator($this->_localSortedProperties);
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum|null ...$kinds
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getLocalPropertiesOfTypeKinds(bool $includeCollections, null|TypeKindEnum...$kinds): iterable
    {
        $out = [];
        foreach ($this->getLocalPropertiesIterator() as $property) {
            if (!$includeCollections && $property->isCollection()) {
                continue;
            }
            $pt = $property->getValueFHIRType();
            if (in_array($pt?->getKind(), $kinds, true)) {
                $out[] = $property;
            }
        }
        return new \ArrayIterator($out);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->properties);
    }

    private function _buildLocalCaches(): void
    {
        if (!$this->cacheBuilt) {
            $this->_sortedProperties = $this->properties;
            $this->_localProperties = [];
            $this->_localSortedProperties = [];
            usort(
                $this->_sortedProperties,
                function (Property $a, Property $b) {
                    return strnatcmp($a->getName(), $b->getName());
                }
            );
            foreach ($this->properties as $property) {
                if (!$property->isOverloaded()) {
                    $this->_localProperties[] = $property;
                }
            }
            foreach ($this->_sortedProperties as $property) {
                if (!$property->isOverloaded()) {
                    $this->_localSortedProperties[] = $property;
                }
            }
            $this->cacheBuilt = true;
        }
    }
}