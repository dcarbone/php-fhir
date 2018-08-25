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
 * Class Type
 * @package DCarbone\PHPFHIR
 */
class Type
{
    use DocumentationTrait;

    const BASE_TYPE_ELEMENT          = 'Element';
    const BASE_TYPE_BACKBONE_ELEMENT = 'BackboneElement';
    const BASE_TYPE_RESOURCE         = 'Resource';
    const BASE_TYPE_DOMAIN_RESOURCE  = 'DomainResource';
    const BASE_TYPE_QUANTITY         = 'Quantity';

    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /** @var \SimpleXMLElement */
    private $sourceSXE;

    /** @var string */
    private $fhirName;
    /** @var null|string */
    private $baseType = null;
    /** @var bool */
    private $component = false;

    /** @var string */
    private $namespace;
    /** @var string */
    private $className;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $parentType = null;

    /** @var \DCarbone\PHPFHIR\Definition\Type\Properties */
    private $properties;

    /**
     * FHIRType constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \SimpleXMLElement $sourceSXE
     * @param string $fhirName
     */
    public function __construct(Config $config, \SimpleXMLElement $sourceSXE, $fhirName)
    {
        $this->config = $config;
        $this->sourceSXE = $sourceSXE;
        $this->fhirName = $fhirName;
        $this->properties = new Properties($config, $this);
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
    public function getFHIRName()
    {
        return $this->fhirName;
    }

    /**
     * @return bool
     */
    public function isBaseType()
    {
        return null !== $this->baseType;
    }

    /**
     * @param string $baseType
     * @return $this
     */
    public function setBaseType($baseType)
    {
        $this->baseType = $baseType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBaseType()
    {
        return $this->baseType;
    }

    /**
     * @param bool $component
     * @return $this
     */
    public function setComponent($component)
    {
        $this->component = $component;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComponent()
    {
        return $this->component;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
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
     * @return string
     */
    public function getFQN()
    {
        return "{$this->namespace}\\{$this->className}";
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
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}