<?php

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Definition\TypeExtractor;
use DCarbone\PHPFHIR\Definition\TypeRelationshipBuilder;

/**
 * Class Definition
 * @package DCarbone\PHPFHIR
 */
class Definition
{
    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /** @var string */
    private $fhirVersion;

    /** @var \DCarbone\PHPFHIR\Definition\Types */
    private $types = null;

    /**
     * Definition constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $fhirVersion
     * @param string $source
     */
    public function __construct(Config $config, $fhirVersion)
    {
        $this->config = $config;
        $this->fhirVersion = $fhirVersion;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'fhirVersion' => $this->fhirVersion,
            'types'       => $this->types,
        ];
    }

    public function buildDefinition()
    {
        $this->config->getLogger()->startBreak('Extracting defined types');
        $this->types = TypeExtractor::parseTypes($this->config);
        TypeRelationshipBuilder::findPropertyTypes($this->config, $this->types);
        $this->config->getLogger()->info(count($this->types) . ' types extracted.');
        $this->config->getLogger()->endBreak('Extracting defined types');
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getFHIRVersion()
    {
        return $this->fhirVersion;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Types|null
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return bool
     */
    public function isDefined()
    {
        return null !== $this->getTypes();
    }
}