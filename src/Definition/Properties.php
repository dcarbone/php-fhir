<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Config\VersionConfig;
use InvalidArgumentException;
use SplFixedArray;

/**
 * Class Properties
 * @package DCarbone\PHPFHIR\Definition\Type
 */
class Properties implements Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\Property[] */
    private array $properties = [];
    /** @var \DCarbone\PHPFHIR\Definition\Property[] */
    private array $_sortedProperties;

    /** @var \DCarbone\PHPFHIR\Definition\Property[] */
    private array $_directProperties;
    /** @var \DCarbone\PHPFHIR\Definition\Property[] */
    private array $_directSortedProperties;

    /** @var bool */
    private bool $cacheBuilt = false;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig*/
    private VersionConfig $config;

    /** @var \DCarbone\PHPFHIR\Definition\Type */
    private Type $type;

    /**
     * Properties constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    public function __construct(VersionConfig $config, Type $type)
    {
        $this->config = $config;
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
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig(): VersionConfig
    {
        return $this->config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @return \DCarbone\PHPFHIR\Definition\Properties
     */
    public function addProperty(Property &$property): Properties
    {
        $pname = $property->getName();
        $pref = $property->getRef();
        if (null === $pname && null === $pref) {
            throw new InvalidArgumentException(
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
     * @return \DCarbone\PHPFHIR\Definition\Property|null
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
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getIterator(): iterable
    {
        return SplFixedArray::fromArray($this->properties, false);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getSortedIterator(): iterable
    {
        $this->_buildLocalCaches();
        return SplFixedArray::fromArray($this->_sortedProperties, false);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getDirectIterator(): iterable
    {
        $this->_buildLocalCaches();
        return SplFixedArray::fromArray($this->_directProperties, false);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getDirectSortedIterator(): iterable
    {
        $this->_buildLocalCaches();
        return SplFixedArray::fromArray($this->_directSortedProperties, false);
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
            $this->_directProperties = [];
            $this->_directSortedProperties = [];
            usort(
                $this->_sortedProperties,
                function (Property $a, Property $b) {
                    return strnatcmp($a->getName(), $b->getName());
                }
            );
            foreach ($this->properties as $property) {
                if (!$property->isOverloaded()) {
                    $this->_directProperties[] = $property;
                }
            }
            foreach ($this->_sortedProperties as $property) {
                if (!$property->isOverloaded()) {
                    $this->_directSortedProperties[] = $property;
                }
            }
            $this->cacheBuilt = true;
        }
    }
}