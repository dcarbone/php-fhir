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
use DCarbone\PHPFHIR\Enum\PropertyUseEnum;

/**
 * Class Property
 * @package DCarbone\PHPFHIR\Definition
 */
class Property
{
    use DocumentationTrait, SourceTrait;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /** @var string */
    private $name = null;

    /** @var string */
    private $valueFHIRTypeName;

    /** @var int */
    private $minOccurs = 0;
    /** @var int */
    private $maxOccurs = 1;

    /** @var string|null */
    private $pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $valueFHIRType = null;

    /** @var \DCarbone\PHPFHIR\Enum\PropertyUseEnum */
    private $use;

    /** @var string */
    private $ref = null;

    /**
     * Property constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \SimpleXMLElement $sxe
     * @param string $sourceFilename
     */
    public function __construct(VersionConfig $config, \SimpleXMLElement $sxe, $sourceFilename)
    {
        $this->config = $config;
        $this->sourceSXE = $sxe;
        $this->sourceFilename = $sourceFilename;
        $this->use = new PropertyUseEnum(PropertyUseEnum::OPTIONAL);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'name'         => $this->getName(),
            'ref'          => $this->getRef(),
            'fhirTypeName' => $this->getValueFHIRTypeName(),
            'sourceSXE'    => $this->getSourceSXE(),
            'minOccurs'    => $this->getMinOccurs(),
            'maxOccurs'    => $this->getMaxOccurs(),
            'pattern'      => $this->getPattern(),
            'fhirType'     => (string)$this->getValueFHIRType(),
        ];
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getValueFHIRTypeName()
    {
        return $this->valueFHIRTypeName;
    }

    /**
     * @param string $valueFHIRTypeName
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setValueFHIRTypeName($valueFHIRTypeName)
    {
        $this->valueFHIRTypeName = $valueFHIRTypeName;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * @param int $maxOccurs
     * @return Property
     */
    public function setMaxOccurs($maxOccurs)
    {
        if (is_string($maxOccurs) && 'unbounded' === strtolower($maxOccurs)) {
            $this->maxOccurs = PHPFHIR_UNLIMITED;
        } else {
            $this->maxOccurs = (int)$maxOccurs;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    /**
     * @param int $minOccurs
     * @return Property
     */
    public function setMinOccurs($minOccurs)
    {
        $this->minOccurs = (int)$minOccurs;
        return $this;
    }

    /**
     * If defined, this is a regex pattern by which to validate the contents.
     *
     * @return null|string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     * @return Property
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getValueFHIRType()
    {
        return $this->valueFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $valueFHIRType
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setValueFHIRType(Type $valueFHIRType)
    {
        $this->valueFHIRType = $valueFHIRType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCollection()
    {
        return PHPFHIR_UNLIMITED === ($o = $this->getMaxOccurs()) || 1 < $o;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return 1 <= $this->getMinOccurs();
    }

    /**
     * @return bool
     */
    public function unlimitedOccurrences()
    {
        return PHPFHIR_UNLIMITED === $this->getMaxOccurs();
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\PropertyUseEnum
     */
    public function getUse()
    {
        return $this->use;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PropertyUseEnum $use
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setUse(PropertyUseEnum $use)
    {
        $this->use = $use;
        return $this;
    }

    /**
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setRef($ref)
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (isset($this->name)) {
            return $this->getName();
        }
        return $this->getRef();
    }
}