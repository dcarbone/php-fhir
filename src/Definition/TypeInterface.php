<?php

namespace DCarbone\PHPFHIR\Definition;

use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Interface TypeInterface
 * @package DCarbone\PHPFHIR\Definition
 */
interface TypeInterface
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
     * If this is a type that exists only as a child of a parent type, this will
     * return the parent type
     *
     * @return null|\DCarbone\PHPFHIR\Definition\Type
     */
    public function getComponentOfType();

    /**
     * Set the parent type this child type belongs to
     *
     * @param \DCarbone\PHPFHIR\Definition\TypeInterface $type
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setComponentOfType(TypeInterface $type);

    /**
     * @return string
     */
    public function getComponentOfTypeName();

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setComponentOfTypeName($name);

    /**
     * Must return an array of all parents till root element, nearest parent first
     *
     * @return \DCarbone\PHPFHIR\Definition\Type[];
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
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
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
     * @return null|\DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function getParentType();

    /**
     * @param \DCarbone\PHPFHIR\Definition\TypeInterface $type
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setParentType(TypeInterface $type);

    /**
     * If the parent of this type is a primitive ("string", for example), there is no "type" to inherit from, thus we
     * store only the name
     *
     * @return null|string
     */
    public function getParentTypeName();

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
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
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
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
     * Returns true if this Type is an element who's only properties are various "valueString",
     * "valueCodeableConcept", etc...
     *
     * @return bool
     */
    public function isVariadicValueElement();

    /**
     * @return bool
     */
    public function isUndefined();

    /**
     * @return bool
     */
    public function isHTML();

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
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setDocumentation($documentation);

    /**
     * @param int $spaces
     * @return string
     */
    public function getDocBlockDocumentationFragment($spaces = 5);
}