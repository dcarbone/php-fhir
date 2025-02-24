<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * Class Properties
 * @package DCarbone\PHPFHIR\Version\Definition\Type
 */
class Properties implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Version\Definition\Property[] */
    private array $_properties = [];
    /** @var \DCarbone\PHPFHIR\Version\Definition\Property[] */
    private array $_sortedProperties;

    /** @var bool */
    private bool $_sorted = false;

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['properties' => $this->_properties];
    }

    public function count(): int
    {
        return count($this->_properties);
    }

    /**
     * Add a property to this type's property list.  The returned property instance MAY NOT be the one you provide!  If
     * the type already has a property of this same name, the original property instance will be returned.
     *
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function addOrReturnProperty(Property $property): Property
    {
        $pname = $property->getName();
        $pref = $property->getRef();
        if (null === $pname && null === $pref) {
            throw new \InvalidArgumentException('Cannot add Property as it has no $name or $ref defined');
        }
        foreach ($this->_properties as $current) {
            if ($property === $current) {
                return $property;
            }
            $cname = $current->getName();
            $cref = $current->getRef();
            if (null !== $pname && null !== $cname && $pname === $cname) {
                return $current;
            } elseif (null !== $pref && null !== $cref && $cref === $pref) {
                return $current;
            }
        }
        $this->_properties[] = $property;
        $this->_sorted = false;
        return $property;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Version\Definition\Property|null
     */
    public function getProperty(string $name): ?Property
    {
        foreach ($this->_properties as $property) {
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
     * Remove a specific property from this property list, if found.
     *
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $target
     * @return void
     */
    public function removeProperty(Property $target): void
    {
        foreach ($this->_properties as $i => $property) {
            if ($property === $target) {
                unset($this->_properties[$i]);
                $this->_properties = array_values($this->_properties);
            }
        }
    }

    /**
     * Remove the specified property from this property list, returning the removed property if found.
     *
     * @param string $name
     * @return \DCarbone\PHPFHIR\Version\Definition\Property|null
     */
    public function removePropertyByName(string $name): null|Property
    {
        foreach ($this->_properties as $i => $property) {
            if ($property->getName() === $name) {
                unset($this->_properties[$i]);
                $this->_properties = array_values($this->_properties);
                return $property;
            }
        }
        return null;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getIterator(): iterable
    {
        if ([] === $this->_properties) {
            return new \EmptyIterator();
        }
        return new \ArrayIterator($this->_properties);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getIndexedIterator(): iterable
    {
        if ([] === $this->_properties) {
            return new \EmptyIterator();
        }
        return \SplFixedArray::fromArray($this->_properties, preserveKeys: false);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getSortedIterator(): iterable
    {
        if ([] === $this->_properties) {
            return new \EmptyIterator();
        }
        $this->_sort();
        return new \ArrayIterator($this->_sortedProperties);
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum|null ...$kinds
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getIteratorOfTypeKinds(bool $includeCollections, null|TypeKindEnum...$kinds): iterable
    {
        $out = [];
        foreach ($this->getIterator() as $property) {
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

    private function _sort(): void
    {
        if ($this->_sorted) {
            return;
        }

        $this->_sortedProperties = $this->_properties;
        usort(
            $this->_sortedProperties,
            function (Property $a, Property $b) {
                return strnatcmp($a->getName(), $b->getName());
            }
        );
        $this->_sorted = true;
    }
}