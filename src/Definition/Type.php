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
use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class Type
 * @package DCarbone\PHPFHIR\Definition
 */
class Type
{
    use DocumentationTrait;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /**
     * The raw element this type was parsed from.  Will be null for HTML and Undefined types
     *
     * @var null|\SimpleXMLElement
     */
    private $sourceSXE;

    /**
     * Name of file in definition this type was parsed from
     * @var string
     */
    private $sourceFilename;
    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private $fhirName;

    /** @var \DCarbone\PHPFHIR\Enum\TypeKind */
    private $kind = null;

    /** @var array */
    private $unionOf = [];
    /** @var \DCarbone\PHPFHIR\Definition\Type\Enumeration */
    private $enumeration;
    /** @var int */
    private $minLength = 0;
    /** @var int */
    private $maxLength = PHPFHIR_UNLIMITED;
    /** @var null|string */
    private $pattern = null;

    /** @var null|string */
    private $componentOfTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $componentOfType = null;

    /** @var string */
    private $className;

    /** @var null|string */
    private $parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $parentType = null;

    /** @var \DCarbone\PHPFHIR\Definition\Type\Properties */
    private $properties;

    /**
     * Type constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string $fhirName
     * @param \SimpleXMLElement|null $sourceSXE
     * @param string $sourceFilename
     */
    public function __construct(VersionConfig $config,
                                $fhirName,
                                \SimpleXMLElement $sourceSXE = null,
                                $sourceFilename = '')
    {
        if ('' === $fhirName) {
            throw new \DomainException('$fhirName must be defined');
        }
        $this->config = $config;
        $this->fhirName = $fhirName;
        $this->sourceSXE = $sourceSXE;
        $this->sourceFilename = $sourceFilename;
        $this->enumeration = new Enumeration();
        $this->properties = new Properties($config, $this);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $vars = get_object_vars($this);
        unset($vars['config']);
        return $vars;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return null|\SimpleXMLElement
     */
    public function getSourceSXE()
    {
        return $this->sourceSXE;
    }

    /**
     * @return string
     */
    public function getSourceFilename()
    {
        return $this->sourceFilename;
    }

    /**
     * @return string
     */
    public function getSourceFileBasename()
    {
        return basename($this->getSourceFilename());
    }

    /**
     * @return string
     */
    public function getFHIRName()
    {
        return $this->fhirName;
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\TypeKind
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKind $kind
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setKind(TypeKind $kind)
    {
        if (isset($this->kind) && !$this->kind->equals($kind)) {
            throw new \LogicException(sprintf(
                'Cannot overwrite Type %s Kind from %s to %s',
                $this->getFHIRName(),
                $this->kind,
                $kind
            ));
        }
        $this->kind = $kind;
        return $this;
    }

    /**
     * @return array
     */
    public function getUnionOf()
    {
        return $this->unionOf;
    }

    /**
     * @param array $unionOf
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setUnionOf(array $unionOf)
    {
        $this->unionOf = $unionOf;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type\Enumeration
     */
    public function getEnumeration()
    {
        return $this->enumeration;
    }

    /**
     * @param mixed $enumValue
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function addEnumerationValue(EnumerationValue $enumValue)
    {
        $this->enumeration->addValue($enumValue);
        return $this;
    }

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @param int $minLength
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMinLength($minLength)
    {
        if (!is_int($minLength)) {
            throw new \InvalidArgumentException(sprintf(
                '$minLength must be int, %s seen',
                gettype($minLength)
            ));
        }
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMaxLength($maxLength)
    {
        if (!is_int($maxLength)) {
            throw new \InvalidArgumentException(sprintf(
                '$maxLength must be int, %s seen',
                gettype($maxLength)
            ));
        }
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string|null $pattern
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getComponentOfType()
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setComponentOfType(Type $type)
    {
        $this->componentOfType = $type;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getComponentOfTypeName()
    {
        return $this->componentOfTypeName;
    }

    /**
     * @param null|string $componentOfTypeName
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setComponentOfTypeName($componentOfTypeName)
    {
        $this->componentOfTypeName = $componentOfTypeName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getParentTypes()
    {
        $parents = [];
        $p = $this->getParentType();
        while (null !== $p) {
            $parents[] = $p;
            $p = $p->getParentType();
        }
        return $parents;
    }

    /**
     * @return string
     */
    public function getFHIRTypeNamespace()
    {
        if ($this->isRootType()) {
            return '';
        }
        $ns = [];
        foreach ($this->getParentTypes() as $parent) {
            array_unshift($ns, $parent->getClassName());
        }
        if ($ctype = $this->getComponentOfType()) {
            $ns[] = $ctype->getClassName();
        }
        return implode('\\', $ns);
    }

    /**
     * @return bool
     */
    public function isRootType()
    {
        return null === $this->getParentType();
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getRootType()
    {
        if ($this->isRootType()) {
            return $this;
        }
        $parents = $this->getParentTypes();
        return end($parents);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        if (!isset($this->className)) {
            $this->className = NameUtils::getTypeClassName($this->getFHIRName());
        }
        return $this->className;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace($leadingSlash)
    {
        $ns = $this->getConfig()->getNamespace();
        $fhirNS = $this->getFHIRTypeNamespace();
        if ('' !== $fhirNS) {
            $ns = "{$ns}\\{$fhirNS}";
        }
        return $leadingSlash ? '\\' . $ns : $ns;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName($leadingSlash)
    {
        return $this->getFullyQualifiedNamespace($leadingSlash) . '\\' . $this->getClassName();
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentType(Type $type)
    {
        $this->parentType = $type;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getParentTypeName()
    {
        return $this->parentTypeName;
    }

    /**
     * @param string|null $parentTypeName
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentTypeName($parentTypeName)
    {
        $this->parentTypeName = $parentTypeName;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return null !== $this->getParentTypeName() || null !== $this->getParentType();
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function addProperty(Property $property)
    {
        $this->properties->addProperty($property);
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type\Properties
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Is this a child of a "primitive" type?
     *
     * @return bool
     */
    public function hasPrimitiveParent()
    {
        foreach ($this->getParentTypes() as $parent) {
            if ($parent->getKind()->isPrimitive()) {
                return true;
            }
        }
        return false;
    }
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

    /**
     * Is this type just a primitive container?
     *
     * TODO: this could stand to be improved, right now only looks for "value" types...
     *
     * @return bool
     */
    public function isPrimitiveContainer()
    {
        return 1 === count($this->properties) &&
            null !== ($prop = $this->properties->getProperty('value')) &&
            null !== ($type = $prop->getValueType()) &&
            ($type->getKind()->isPrimitive() || $type->hasPrimitiveParent());
    }

    /**
     * Does this type extend a type that is a primitive container?
     *
     * @return bool
     */
    public function hasPrimitiveContainerParent()
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->isPrimitiveContainer()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasResourceParent()
    {
        foreach ($this->getParentTypes() as $parentType) {
            $kind = $parentType->getKind();
            if ($kind->isResource() || $kind->isDomainResource()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if this Type is an element who's only properties are various "valueString",
     * "valueCodeableConcept", etc...
     *
     * @return bool
     */
    public function isVariadicValueElement()
    {
        $kind = $this->getKind();
        if ($kind->isTypeValue() || $kind->isPrimitive() || $kind->isList() || $this->isPrimitiveContainer()) {
            return false;
        }
        if (1 < count($this->properties)) {
            foreach ($this->getProperties()->getIterator() as $property) {
                $name = $property->getName();
                if ('value' !== $name && 0 === strpos($property->getName(), 'value')) {
                    continue;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}