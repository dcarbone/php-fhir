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

/**
 * Class ListType
 * @package DCarbone\PHPFHIR\Definition
 */
class ListType extends AbstractType
{
    /** @var \DCarbone\PHPFHIR\Definition\Type\Enumeration */
    private $enumeration;

    /** @var string */
    private $restrictionBaseFHIRName;

    /**
     * ListType constructor.
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
        $this->enumeration = new Enumeration();
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
     * @return \DCarbone\PHPFHIR\Definition\ListType
     */
    public function addEnumerationValue(EnumerationValue $enumValue)
    {
        $this->enumeration->addValue($enumValue);
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
     * @return \DCarbone\PHPFHIR\Definition\ListType
     */
    public function setRestrictionBaseFHIRName($restrictionBaseFHIRName)
    {
        $this->restrictionBaseFHIRName = $restrictionBaseFHIRName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeNamespace()
    {
        return '';
    }
}