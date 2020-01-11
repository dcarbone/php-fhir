<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * Class Types
 * @package DCarbone\PHPFHIR
 */
class Types implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\Type[] */
    private $types = [];

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /**
     * This is the type that is used as a proxy type for a multitude of other types!
     * @var \DCarbone\PHPFHIR\Definition\Type
     */
    private $containerType;

    /**
     * FHIRTypes constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     */
    public function __construct(VersionConfig $config)
    {
        $this->config = $config;
        $rawType = build_raw_type($config);
        $this->addType($rawType);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['types' => $this->types];
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getFHIRName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByClassName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getClassName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $fqn
     * @param bool $leadingSlash
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByFQN($fqn, $leadingSlash)
    {
        foreach ($this->types as $type) {
            if ($type->getFullyQualifiedClassName($leadingSlash) === $fqn) {
                return $type;
            }
        }
        return null;
    }

    /**
     * This method has a reference receiver as we do not want to have two separate instances of the
     * same type running around...
     *
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Types
     */
    public function addType(Type &$type)
    {
        $tname = $type->getFHIRName();
        foreach ($this->types as $current) {
            if ($type === $current) {
                return $this;
            }
            if ($current->getFHIRName() === $tname) {
                // this happens with FHIR types sometimes...
                $this->config->getLogger()->notice(sprintf(
                    'Type "%s" was previously defined in file "%s", found again in "%s".  Keeping original',
                    $tname,
                    $current->getSourceFileBasename(),
                    $type->getSourceFileBasename()
                ));
                $type = $current;
                return $this;
            }
        }
        $this->types[] = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->types);
    }

    /**
     * Returns iterator of types natcase sorted by FHIR type name
     *
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getSortedIterator()
    {
        $tmp = $this->types;
        usort(
            $tmp,
            function (Type $t1, Type $t2) {
                return strnatcasecmp($t1->getFHIRName(), $t2->getFHIRName());
            }
        );
        return new \ArrayIterator($tmp);
    }

    /**
     * Returns iterator of types natcase sorted by FHIR type name
     *
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */

    public function getNamespaceSortedIterator()
    {
        $tmp = $this->types;
        usort(
            $tmp,
            function (Type $t1, Type $t2) {
                return strnatcasecmp($t1->getFullyQualifiedClassName(false), $t2->getFullyQualifiedClassName(false));
            }
        );
        return new \ArrayIterator($tmp);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getContainerType()
    {
        if (!isset($this->containerType)) {
            foreach ($this->types as $type) {
                if ($type->getKind()->isOneOf([TypeKindEnum::RESOURCE_INLINE, TypeKindEnum::RESOURCE_CONTAINER])) {
                    $this->containerType = $type;
                }
            }
        }
        return isset($this->containerType) ? $this->containerType : null;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return bool
     */
    public function isContainedType(Type $type)
    {
        static $ignoredTypes = [
            TypeKindEnum::RESOURCE_INLINE,
            TypeKindEnum::RESOURCE_CONTAINER,
            TypeKindEnum::_LIST,
            TypeKindEnum::PRIMITIVE,
            TypeKindEnum::PRIMITIVE_CONTAINER,
        ];

        // only bother with actual Resource types.
        if ($type->getKind()->isOneOf($ignoredTypes)) {
            return false;
        }
        $container = $this->getContainerType();
        if (null === $container) {
            return false;
        }
        foreach ($container->getProperties()->getIterator() as $property) {
            if (($ptype = $property->getValueFHIRType()) && $ptype->getFHIRName() === $type->getFHIRName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->types);
    }
}