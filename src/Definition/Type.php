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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type\Properties;
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * TODO: Tracking too much state in this class...
 *
 * Class Type
 * @package DCarbone\PHPFHIR
 */
class Type
{
    use DocumentationTrait;

    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /**
     * The raw element this type was parsed from
     * @var \SimpleXMLElement
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

    /** @var null|\DCarbone\PHPFHIR\Enum\PrimitiveType */
    private $primitiveType;

    /** @var \DCarbone\PHPFHIR\Definition\Type\Properties */
    private $properties;

    /**
     * FHIRType constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \SimpleXMLElement $sourceSXE
     * @param string $sourceFilename
     * @param string $fhirName
     */
    public function __construct(Config $config, \SimpleXMLElement $sourceSXE, $sourceFilename, $fhirName)
    {
        $this->config = $config;
        $this->sourceSXE = $sourceSXE;
        $this->sourceFilename = $sourceFilename;
        $this->fhirName = $fhirName;
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
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \SimpleXMLElement
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
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getComponentOfType()
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type|null $componentOfType
     * @return Type
     */
    public function setComponentOfType(Type $componentOfType)
    {
        $this->componentOfType = $componentOfType;
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
     * @return Type
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
     * @return $this|\DCarbone\PHPFHIR\Definition\Type
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
        return $this->className;
    }

    /**
     * @param $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace($leadingSlash)
    {
        $ns = $this->getConfig()->getOutputNamespace();
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
     * Set the Type this Type inherits from
     *
     * @param \DCarbone\PHPFHIR\Definition\Type $parentType
     * @return $this
     */
    public function setParentType(Type $parentType)
    {
        $this->parentType = $parentType;
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
     * @param null|string $parentTypeName
     * @return Type
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
        return null !== $this->getParentTypeName();
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return $this
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
     * @return bool
     */
    public function isResourceContainer()
    {
        return PHPFHIR_TYPE_RESOURCE_CONTAINER === $this->getFHIRName();
    }

    /**
     * @return bool
     */
    public function isInlineResource()
    {
        return PHPFHIR_TYPE_RESOURCE_INLINE === $this->getFHIRName();
    }

    /**
     * Is this a child of a "primitive" type?
     *
     * @return bool
     */
    public function hasPrimitiveParent()
    {
        foreach ($this->getParentTypes() as $parent) {
            if ($parent->isPrimitive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is this immediate type a "primitive"?
     *
     * @return bool
     */
    public function isPrimitive()
    {
        return false !== strpos($this->getFHIRName(), '-primitive');
    }

    /**
     * @return bool
     */
    public function isList()
    {
        return false !== strpos($this->getFHIRName(), '-list');
    }

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
            ($type->isPrimitive() || $type->hasPrimitiveParent());
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
    public function isResource()
    {
        $n = $this->getFHIRName();
        if (PHPFHIR_TYPE_RESOURCE === $n || PHPFHIR_TYPE_DOMAIN_RESOURCE === $n) {
            return true;
        }
        foreach ($this->getParentTypes() as $parentType) {
            $n = $parentType->getFHIRName();
            if (PHPFHIR_TYPE_RESOURCE === $n || PHPFHIR_TYPE_DOMAIN_RESOURCE === $n) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if this Type is an element who's only property is a "value" of some sort
     *
     * @return bool
     */
    public function isValueElement()
    {
        if ($this->isPrimitive() || $this->isPrimitiveContainer() || $this->isList()) {
            return false;
        }
    }

    /**
     * Returns true if this Type is an element who's only properties are various "valueString",
     * "valueCodeableConcept", etc...
     *
     * @return bool
     */
    public function isVariadicValueElement()
    {
        if ($this->isPrimitive() || $this->isPrimitiveContainer() || $this->isList()) {
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