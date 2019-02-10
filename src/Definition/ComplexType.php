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
use DCarbone\PHPFHIR\Definition\Type\Properties;
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Class ComplexType
 * @package DCarbone\PHPFHIR\Definition
 */
class ComplexType extends AbstractType
{
    /** @var null|string */
    private $componentOfTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\ComplexType */
    private $componentOfType = null;

    /** @var null|string */
    private $parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\ComplexType */
    private $parentType = null;

    /** @var \DCarbone\PHPFHIR\Definition\Type\Properties */
    private $properties;

    /**
     * ComplexType constructor.
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
        parent::__construct($config, $fhirName, $sourceSXE, $sourceFilename);
        $this->properties = new Properties($config, $this);
    }

    /**
     * @return string
     */
    public function getTypeNamespace()
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
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return \DCarbone\PHPFHIR\Definition\ComplexType
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
     * @return \DCarbone\PHPFHIR\Definition\ComplexType|null
     */
    public function getComponentOfType()
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\ComplexType
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
     * @return \DCarbone\PHPFHIR\Definition\ComplexType
     */
    public function setComponentOfTypeName($componentOfTypeName)
    {
        $this->componentOfTypeName = $componentOfTypeName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\ComplexType[]
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
     * @return \DCarbone\PHPFHIR\Definition\ComplexType|null
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\ComplexType $type
     * @return \DCarbone\PHPFHIR\Definition\ComplexType
     */
    public function setParentType(ComplexType $type)
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
     * @return \DCarbone\PHPFHIR\Definition\ComplexType
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
}