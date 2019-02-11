<?php

namespace DCarbone\PHPFHIR;

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
use DCarbone\PHPFHIR\Definition\TypeExtractor;
use DCarbone\PHPFHIR\Definition\TypeDecorator;

/**
 * Class Definition
 * @package DCarbone\PHPFHIR
 */
class Definition
{
    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /** @var \DCarbone\PHPFHIR\Definition\Types */
    private $types = null;

    /**
     * Definition constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     */
    public function __construct(VersionConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'types' => $this->types,
        ];
    }

    public function buildDefinition()
    {
        $this->config->getLogger()->startBreak('Extracting defined types');

        $this->config->getLogger()->info('Parsing types');
        $this->types = TypeExtractor::parseTypes($this->config);

        $this->config->getLogger()->info('Finding parent types');
        TypeDecorator::findParentTypes($this->config, $this->types);

        $this->config->getLogger()->info('Finding component types');
        TypeDecorator::findComponentOfTypes($this->config, $this->types);

        // TODO: order of operations issue here, ideally this would be first...
        $this->config->getLogger()->info('Determining type kinds');
        TypeDecorator::determineParsedTypeKinds($this->config, $this->types);

        $this->config->getLogger()->info('Finding property types');
        TypeDecorator::findPropertyTypes($this->config, $this->types);

        $this->config->getLogger()->info(count($this->types) . ' types extracted.');
        $this->config->getLogger()->endBreak('Extracting defined types');
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig()
    {
        return $this->config;
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