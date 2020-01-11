<?php

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class Version
 * @package DCarbone\PHPFHIR\Config
 */
class Version
{
    const KEY_URL           = 'url';
    const KEY_NAMESPACE     = 'namespace';
    const KEY_TEST_ENDPOINT = 'testEndpoint';

    /** @var string */
    private $name;

    /** @var string */
    private $url;
    /** @var string */
    private $namespace;
    /** @var string */
    private $testEndpoint;

    /**
     * Version constructor.
     * @param string $name
     * @param array $conf
     */
    public function __construct($name, array $conf = [])
    {
        $this->name = $name;

        if (!isset($conf[self::KEY_URL])) {
            throw new\DomainException(sprintf('Version %s is missing required config key ', self::KEY_URL));
        }
        $this->setUrl($conf[self::KEY_URL]);

        if (isset($conf[self::KEY_NAMESPACE])) {
            $this->setNamespace($conf[self::KEY_NAMESPACE]);
        }

        if (isset($conf[self::KEY_TEST_ENDPOINT])) {
            $this->setTestEndpoint($conf[self::KEY_TEST_ENDPOINT]);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function setUrl($url)
    {
        if (!is_string($url)) {
            throw new \InvalidArgumentException(sprintf(
                '$url must be a string, %s seen.',
                gettype($url)
            ));
        }
        $this->url = $url;
        return $this;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getNamespace($leadingSlash)
    {
        return $leadingSlash ? "\\{$this->namespace}" : $this->namespace;
    }

    /**
     * @param string $namespace
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function setNamespace($namespace)
    {
        if (null === $namespace) {
            $this->namespace = '';
            return $this;
        }
        if (!is_string($namespace)) {
            throw new \InvalidArgumentException(sprintf(
                '$namespace must be a string, %s seen.',
                gettype($namespace)
            ));
        }
        // handle no or empty namespace
        $namespace = trim((string)$namespace, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if ('' === $namespace) {
            $this->namespace = '';
            return $this;
        }

        if (false === NameUtils::isValidNSName($namespace)) {
            throw new \InvalidArgumentException(sprintf(
                'Version "%s" namespace "%s" is not a valid PHP namespace.',
                $this->name,
                $this->namespace
            ));
        }

        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTestEndpoint()
    {
        return isset($this->testEndpoint) ? $this->testEndpoint : null;
    }

    /**
     * @param string $testEndpoint
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function setTestEndpoint($testEndpoint)
    {
        if (!is_string($testEndpoint)) {
            throw new \InvalidArgumentException(sprintf(
                '$testEndpoint must be a string, %s seen.',
                gettype($testEndpoint)
            ));
        }
        $this->testEndpoint = $testEndpoint;
        return $this;
    }
}