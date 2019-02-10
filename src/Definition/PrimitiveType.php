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
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * Class PrimitiveType
 * @package DCarbone\PHPFHIR\Definition
 */
class PrimitiveType extends AbstractType
{
    /** @var array */
    private $unionOf = [];
    /** @var int */
    private $minLength = 0;
    /** @var int */
    private $maxLength = PHPFHIR_UNLIMITED;
    /** @var null|string */
    private $pattern = null;

    /** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum */
    private $primitiveType;

    /** @var string */
    private $restrictionBaseFHIRName;

    /**
     * PrimitiveType constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param $fhirName
     * @param \SimpleXMLElement|null $sourceSXE
     * @param string $sourceFilename
     */
    public function __construct(VersionConfig $config,
                                $fhirName,
                                \SimpleXMLElement $sourceSXE = null,
                                $sourceFilename = '')
    {
        parent::__construct($config, $fhirName, $sourceSXE, $sourceFilename);
        $this->setKind(new TypeKindEnum(TypeKindEnum::PRIMITIVE));
        $this->primitiveType = new PrimitiveTypeEnum(str_replace(PHPFHIR_PRIMITIVE_SUFFIX, '', $fhirName));
    }

    /**
     * All primitive types are to be placed in the root namespace
     *
     * @return string
     */
    public function getTypeNamespace()
    {
        return '';
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum
     */
    public function getPrimitiveType()
    {
        return $this->primitiveType;
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
     * @return \DCarbone\PHPFHIR\Definition\PrimitiveType
     */
    public function setUnionOf(array $unionOf)
    {
        $this->unionOf = $unionOf;
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
     * @return \DCarbone\PHPFHIR\Definition\PrimitiveType
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
     * @return \DCarbone\PHPFHIR\Definition\PrimitiveType
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
     * @return \DCarbone\PHPFHIR\Definition\PrimitiveType
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getRestrictionBaseFHIRName()
    {
        return $this->restrictionBaseFHIRName;
    }

    /**
     * @param string $restrictionBaseFHIRName
     * @return \DCarbone\PHPFHIR\Definition\PrimitiveType
     */
    public function setRestrictionBaseFHIRName($restrictionBaseFHIRName)
    {
        $this->restrictionBaseFHIRName = $restrictionBaseFHIRName;
        return $this;
    }
}