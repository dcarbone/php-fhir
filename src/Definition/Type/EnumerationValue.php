<?php

namespace DCarbone\PHPFHIR\Definition\Type;

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

use DCarbone\PHPFHIR\Definition\DocumentationTrait;

/**
 * Class EnumerationValue
 * @package DCarbone\PHPFHIR\Definition\Type
 */
class EnumerationValue
{
    use DocumentationTrait;

    /** @var mixed */
    private $value;
    /** @var \SimpleXMLElement */
    private $sourceSXE;

    /**
     * Enumeration constructor.
     * @param string $value
     * @param \SimpleXMLElement $sourceSXE
     */
    public function __construct($value, \SimpleXMLElement $sourceSXE)
    {
        $this->value = $value;
        $this->sourceSXE = $sourceSXE;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
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
    public function __toString()
    {
        return (string)$this->getValue();
    }
}