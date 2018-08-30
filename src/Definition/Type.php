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
    const RESOURCE_CONTAINER = 'ResourceContainer';

    use DocumentationTrait;

    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /** @var \SimpleXMLElement */
    private $sourceSXE;

    /** @var string */
    private $sourceFilename;
    /** @var string */
    private $fhirName;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $componentOfType = null;

    /** @var string */
    private $className;

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
     * @param $sourceFilename
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
        return self::RESOURCE_CONTAINER === $this->getFHIRName();
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
     * Is this type just a primitive container?
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
    public function isList()
    {
        return false !== strpos($this->getFHIRName(), '-list');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}