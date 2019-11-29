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

use DCarbone\PHPFHIR\Enum\PropertyUseEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class Property
 * @package DCarbone\PHPFHIR\Definition
 */
class Property
{
    use DocumentationTrait, SourceTrait;

    /** @var \DCarbone\PHPFHIR\Definition\Type */
    private $memberOf;

    /** @var string */
    private $name = null;

    /** @var string|null */
    private $valueFHIRTypeName = null;

    /** @var null|string */
    private $rawPHPValue = null;

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

    /** @var null|string */ // TODO: what the hell is this...?
    private $fixed = null;

    /** @var null|string */ // NOTE: not a php namespace
    private $namespace = null;

    /** @var bool */
    private $overloaded = false;

    /**
     * Property constructor.
     * @param \DCarbone\PHPFHIR\Definition\Type $memberOf
     * @param \SimpleXMLElement $sxe
     * @param string $sourceFilename
     */
    public function __construct(Type $memberOf, \SimpleXMLElement $sxe, $sourceFilename)
    {
        $this->memberOf = $memberOf;
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
            'rawPHPValue'  => $this->getRawPHPValue(),
            'fhirType'     => (string)$this->getValueFHIRType(),
            'use'          => (string)$this->getUse(),
            'fixed'        => (string)$this->getFixed(),
            'namespace'    => (string)$this->getNamespace(),
        ];
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getMemberOf()
    {
        return $this->memberOf;
    }

    /**
     * @return string|null
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
        if (!is_string($name) || '' === $name) {
            throw new \InvalidArgumentException(sprintf(
                'Type "%s" Property $name cannot be empty',
                $this->valueFHIRType->getFHIRName()
            ));
        }
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
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
        $this->valueFHIRTypeName = $valueFHIRType->getFHIRName();
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRawPHPValue()
    {
        return $this->rawPHPValue;
    }

    /**
     * @param string|null $rawPHPValue
     * @return Property
     */
    public function setRawPHPValue($rawPHPValue)
    {
        $this->rawPHPValue = $rawPHPValue;
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
     * @return string|null
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
        if (!is_string($ref) || '' === $ref) {
            throw new \InvalidArgumentException(sprintf(
                'Type "%s" Property $ref cannot be empty',
                $this->valueFHIRType->getFHIRName()
            ));
        }
        $this->ref = $ref;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * @param string|null $fixed
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string|null $namespace
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldConstantName()
    {
        return 'FIELD_' . NameUtils::getConstName($this->getName());
    }

    /**
     * @return string
     */
    public function getFieldConstantExtensionName()
    {
        return $this->getFieldConstantName() . '_EXT';
    }

    /**
     * @return bool
     */
    public function isValueProperty()
    {
        return PHPFHIR_VALUE_PROPERTY_NAME === $this->getName();
    }

    /**
     * @return array
     */
    public function buildValidationMap()
    {
        $map = [];
        $memberOf = $this->getMemberOf();

        if (null !== ($v = $this->getPattern())) {
            $map[PHPFHIR_VALIDATION_PATTERN_NAME] = '/^' . addcslashes($v, '/\'') . '$/';
        } elseif (null !== ($v = $memberOf->getPattern())) {
            $map[PHPFHIR_VALIDATION_PATTERN_NAME] = '/^' . addcslashes($v, '/\'') . '$/';
        }

        if ($this->isCollection()) {
            if (null !== ($v = $this->getMinOccurs()) && 0 !== $v) {
                $map[PHPFHIR_VALIDATION_MIN_OCCURS_NAME] = $v;
            }
            if (null !== ($v = $this->getMaxOccurs()) && PHPFHIR_UNLIMITED !== $v) {
                $map[PHPFHIR_VALIDATION_MAX_OCCURS_NAME] = $v;
            }
        }

        if (null !== ($v = $memberOf->getMinLength()) && 0 !== $v) {
            $map[PHPFHIR_VALIDATION_MIN_LENGTH_NAME] = $v;
        }
        if (null !== ($v = $memberOf->getMaxLength()) && PHPFHIR_UNLIMITED !== $v) {
            $map[PHPFHIR_VALIDATION_MAX_LENGTH_NAME] = $v;
        }

        if ($memberOf->isEnumerated()) {
            $map[PHPFHIR_VALIDATION_ENUM_NAME] = [];
            foreach ($memberOf->getEnumeration() as $enum) {
                $map[PHPFHIR_VALIDATION_ENUM_NAME][] = $enum->getValue();
            }
        }

        return $map;
    }

    /**
     * @param bool $overloaded
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setOverloaded($overloaded)
    {
        $this->overloaded = (bool)$overloaded;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOverloaded()
    {
        return $this->overloaded;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($n = $this->getName()) {
            return $n;
        } elseif ($n = $this->getRef()) {
            return $n;
        } else {
            return 'UNKNOWN';
        }
    }
}