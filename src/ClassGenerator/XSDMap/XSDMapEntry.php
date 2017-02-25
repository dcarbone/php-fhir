<?php namespace DCarbone\PHPFHIR\ClassGenerator\XSDMap;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class XSDMapEntry
 * @package DCarbone\PHPFHIR\ClassGenerator\XSDMap
 */
class XSDMapEntry
{
    /** @var \SimpleXMLElement */
    public $sxe;
    /** @var string */
    public $fhirElementName;
    /** @var string */
    public $namespace;
    /** @var string */
    public $className;

    /**
     * Constructor
     *
     * @param \SimpleXMLElement $sxe
     * @param string $fhirElementName
     * @param string $namespace
     * @param string $className
     */
    public function __construct(\SimpleXMLElement $sxe,
                                $fhirElementName,
                                $namespace,
                                $className)
    {
        $this->sxe = $sxe;
        $this->fhirElementName = $fhirElementName;
        $this->namespace = $namespace;
        $this->className = $className;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getSxe()
    {
        return $this->sxe;
    }

    /**
     * @return string
     */
    public function getFHIRElementName()
    {
        return $this->fhirElementName;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}