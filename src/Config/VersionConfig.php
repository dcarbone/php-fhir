<?php

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class VersionConfig
 * @package DCarbone\PHPFHIR\Config
 */
class VersionConfig
{
    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /** @var \DCarbone\PHPFHIR\Config\Version */
    private $version;

    /**
     * BuildConfig constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Config\Version $version
     */
    public function __construct(Config $config, Config\Version $version)
    {
        $this->config = $config;
        $this->version = $version;
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger()
    {
        return $this->config->getLogger();
    }

    /**
     * @return string
     */
    public function getSchemaPath()
    {
        return "{$this->config->getSchemaPath()}/{$this->version->getName()}";
    }

    /**
     * @return string
     */
    public function getClassesPath()
    {
        return $this->config->getClassesPath();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->version->getUrl();
    }

    /**
     * @param boolean $leadingSlash
     * @return string
     */
    public function getNamespace($leadingSlash)
    {
        return $this->version->getNamespace($leadingSlash);
    }

    /**
     * @return bool
     */
    public function isSkipTests()
    {
        return $this->config->isSkipTests();
    }

    /**
     * @param boolean $leadingSlash
     * @return string
     */
    public function getTestsNamespace($leadingSlash)
    {
        $ns = $this->getNamespace(false);
        if ('' === $ns) {
            $ns = PHPFHIR_TESTS_NAMESPACE;
        } else {
            $ns .= '\\' . PHPFHIR_TESTS_NAMESPACE;
        }
        return $leadingSlash ? "\\{$ns}" : $ns;
    }

    /**
     * @return string|null
     */
    public function getTestEndpoint()
    {
        return $this->version->getTestEndpoint();
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function getVersion()
    {
        return $this->version;
    }
}