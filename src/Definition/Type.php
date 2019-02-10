<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Definition\Type\Enumeration;
use DCarbone\PHPFHIR\Definition\Type\EnumerationValue;
use DCarbone\PHPFHIR\Definition\Type\Properties;
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class Type
 * @package DCarbone\PHPFHIR\Definition
 */
interface Type
{
    /**
     * @return string
     */
    public function getFHIRName();

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig();

    /**
     * @return null|\SimpleXMLElement
     */
    public function getSourceSXE();

    /**
     * @return string
     */
    public function getSourceFilename();

    /**
     * @return string
     */
    public function getSourceFileBasename();

    /**
     * @return \DCarbone\PHPFHIR\Enum\TypeKindEnum
     */
    public function getKind();

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum $kind
     * @return $this
     */
    public function setKind(TypeKindEnum $kind);

    /**
     * @return string
     */
    public function getClassName();

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace($leadingSlash);

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName($leadingSlash);

    /**
     * @return string
     */
    public function getTypeNamespace();

    /**
     * @return string
     */
    public function __toString();

//    /**
//     * Is this a child of a "primitive" type?
//     *
//     * @return bool
//     */
//    public function hasPrimitiveParent()
//    {
//        foreach ($this->getParentTypes() as $parent) {
//            if ($parent->getKind()->isPrimitive()) {
//                return true;
//            }
//        }
//        return false;
//    }
//
//    /**
//     * Is this immediate type a "primitive"?
//     *
//     * @return bool
//     */
//    public function isPrimitive()
//    {
//        return false !== strpos($this->getFHIRName(), '-primitive');
//    }
//
//    /**
//     * @return bool
//     */
//    public function isList()
//    {
//        return false !== strpos($this->getFHIRName(), '-list');
//    }

//    /**
//     * Is this type just a primitive container?
//     *
//     * TODO: this could stand to be improved, right now only looks for "value" types...
//     *
//     * @return bool
//     */
//    public function isPrimitiveContainer()
//    {
//        return 1 === count($this->properties) &&
//            null !== ($prop = $this->properties->getProperty('value')) &&
//            null !== ($type = $prop->getValueType()) &&
//            ($type->getKind()->isPrimitive());
//    }

//    /**
//     * Does this type extend a type that is a primitive container?
//     *
//     * @return bool
//     */
//    public function hasPrimitiveContainerParent()
//    {
//        foreach ($this->getParentTypes() as $parentType) {
//            if ($parentType->isPrimitiveContainer()) {
//                return true;
//            }
//        }
//        return false;
//    }

//    /**
//     * Returns true if this Type is an element who's only properties are various "valueString",
//     * "valueCodeableConcept", etc...
//     *
//     * @return bool
//     */
//    public function isVariadicValueElement()
//    {
//        $kind = $this->getKind();
//        if ($kind->isTypeValue() || $kind->isPrimitive() || $kind->isList() || $this->isPrimitiveContainer()) {
//            return false;
//        }
//        if (1 < count($this->properties)) {
//            foreach ($this->getProperties()->getIterator() as $property) {
//                $name = $property->getName();
//                if ('value' !== $name && 0 === strpos($property->getName(), 'value')) {
//                    continue;
//                }
//                return false;
//            }
//            return true;
//        }
//        return false;
//    }


}