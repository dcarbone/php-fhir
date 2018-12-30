<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Definition\Type\EnumerationValue;
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Interface Type
 * @package DCarbone\PHPFHIR\Definition
 */
interface Type
{
    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig();

    /**
     * Root SimpleXMLElement that defines this type
     *
     * This will only be null if the type is "undefined"
     *
     * @return null|\SimpleXMLElement
     */
    public function getSourceSXE();

    /**
     * Name of the source XSD file this type's definition was extracted from.
     *
     * Will only be empty for "undefined" types
     *
     * @return string
     */
    public function getSourceFilename();

    /**
     * @return string
     */
    public function getSourceFileBasename();

    /**
     * The "name" this type is referred to by within the FHIR spec
     *
     * @return string
     */
    public function getFHIRName();

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getUnionOf();

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type[] $unionOf
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setUnionOf(array $unionOf);

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type\Enumeration
     */
    public function getEnumeration();

    /**
     * @param mixed $enumValue
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function addEnumerationValue(EnumerationValue $enumValue);

    /**
     * @return int
     */
    public function getMinLength();

    /**
     * @param int $minLength
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMinlength($minLength);

    /**
     * @return int
     */
    public function getMaxLength();

    /**
     * @param int $maxLength
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMaxLength($maxLength);

    /**
     * @return string
     */
    public function getPattern();

    /**
     * @param string $pattern
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setPattern($pattern);

    /**
     * If this is a type that exists only as a child of a parent type, this will
     * return the parent type
     *
     * @return null|\DCarbone\PHPFHIR\Definition\Type\StandardType
     */
    public function getComponentOfType();

    /**
     * Set the parent type this child type belongs to
     *
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setComponentOfType(Type $type);

    /**
     * @return string
     */
    public function getComponentOfTypeName();

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setComponentOfTypeName($name);

    /**
     * Must return an array of all parents till root element, nearest parent first
     *
     * @return \DCarbone\PHPFHIR\Definition\Type\StandardType[];
     */
    public function getParentTypes();

    /**
     * Must return the full path to the namespace preceding this type
     *
     * @return string
     */
    public function getFHIRTypeNamespace();

    /**
     * Must return true if this type has no parent type
     *
     * @return bool
     */
    public function isRootType();

    /**
     * If this is a root type, return self.  If has parents, returns furthest parent
     *
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getRootType();

    /**
     * Must the PHP-ized class name of this type
     *
     * @return string
     */
    public function getClassName();

    /**
     * @param bool $leadingSlash Whether to prefix response with "\"
     * @return string
     */
    public function getFullyQualifiedNamespace($leadingSlash);

    /**
     * @param bool $leadingSlash Whether to prefix response with "\"
     * @return string
     */
    public function getFullyQualifiedClassname($leadingSlash);

    /**
     * @return null|\DCarbone\PHPFHIR\Definition\Type
     */
    public function getParentType();

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentType(Type $type);

    /**
     * If the parent of this type is a primitive ("string", for example), there is no "type" to inherit from, thus we
     * store only the name
     *
     * @return null|string
     */
    public function getParentTypeName();

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentTypeName($name);

    /**
     * Must return true if either parent type or parent type name are set
     *
     * @return bool
     */
    public function hasParent();

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function addProperty(Property $property);

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type\Properties
     */
    public function getProperties();

    /**
     * @return bool
     */
    public function isResourceContainer();

    /**
     * @return bool
     */
    public function isInlineResource();

    /**
     * @return bool
     */
    public function isPrimitive();

    /**
     * @return bool
     */
    public function hasPrimitiveParent();

    /**
     * @return bool
     */
    public function isList();

    /**
     * @return bool
     */
    public function isPrimitiveContainer();

    /**
     * @return bool
     */
    public function hasPrimitiveContainerParent();

    /**
     * @return bool
     */
    public function isResource();

    /**
     * @return bool
     */
    public function isUndefined();

    /**
     * @return bool
     */
    public function isHTML();

    /**
     * This will only return true for the "value" field on primitive types
     *
     * @return bool
     */
    public function isPrimitiveTypeValueType();

    /**
     * Returns true if this Type is an element who's only properties are various "valueString",
     * "valueCodeableConcept", etc...
     *
     * @return bool
     */
    public function isVariadicValueElement();

    /**
     * @return array
     */
    public function getDocumentation();

    /**
     * @return string
     */
    public function getDocumentationString();

    /**
     * @param null|string|array $documentation
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setDocumentation($documentation);

    /**
     * @param int $spaces
     * @return string
     */
    public function getDocBlockDocumentationFragment($spaces = 5);
}