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
use DCarbone\PHPFHIR\Enum\BaseType;
use DCarbone\PHPFHIR\Enum\SimpleType;

/**
 * Class Type
 * @package DCarbone\PHPFHIR
 */
class Type
{
    use DocumentationTrait;

    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /** @var \SimpleXMLElement */
    private $sourceSXE;

    /** @var string */
    private $sourceFilename;
    /** @var string */
    private $fhirName;
    /** @var null|\DCarbone\PHPFHIR\Enum\BaseType */
    private $baseType = null;
    /** @var bool */
    private $component = false;

    /** @var string */
    private $className;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $parentType = null;

    /** @var null|\DCarbone\PHPFHIR\Enum\SimpleType */
    private $simpleType = null;

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
     * @return bool
     */
    public function isBaseType()
    {
        return null !== $this->baseType;
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\BaseType|null
     */
    public function getBaseType()
    {
        return $this->baseType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\BaseType|null $baseType
     * @return Type
     */
    public function setBaseType(BaseType $baseType)
    {
        $this->baseType = $baseType;
        return $this;
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
    public function getFHIRTypeNamespace()
    {
        if ($this->isComponent()) {
            return sprintf('FHIRResources\\FHIR%s', strstr($this->getFHIRName(), '.', true));
        }
        if (!$this->isBaseType()) {
            return '';
        }
        switch ($bt = $this->getBaseType()) {
            case BaseType::ELEMENT:
            case BaseType::BACKBONE_ELEMENT:
            case BaseType::RESOURCE:
            case BaseType::DOMAIN_RESOURCE:
            case BaseType::QUANTITY:
                return "FHIR{$bt}";

            default:
                throw new \LogicException(sprintf(
                    'Type %s has unknown Base Type %s',
                    $this,
                    $bt
                ));
        }
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
     * @return \DCarbone\PHPFHIR\Enum\SimpleType|null
     */
    public function getSimpleType()
    {
        return $this->simpleType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\SimpleType $simpleType
     * @return Type
     */
    public function setSimpleType(SimpleType $simpleType)
    {
        $this->simpleType = $simpleType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSimpleType()
    {
        return null !== $this->getSimpleType();
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFQN($leadingSlash)
    {
        $fhirNS = $this->getFHIRTypeNamespace();
        if ('' === $fhirNS) {
            $ns = sprintf('%s\\%s', $this->config->getOutputNamespace(), $this->getClassName());
        } else {
            $ns = sprintf('%s\\%s\\%s', $this->config->getOutputNamespace(), $fhirNS, $this->getClassName());
        }
        return $leadingSlash ? '\\' . $ns : $ns;
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