<?php

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    const KEY_URL       = 'url';
    const KEY_NAMESPACE = 'namespace';

    /** @var string */
    private $name;

    /** @var string */
    private $url;
    /** @var string */
    private $namespace;

    /**
     * Version constructor.
     * @param string $name
     * @param array $conf
     */
    public function __construct($name, array $conf = [])
    {
        $this->name = $name;
        if (!isset($conf[self::KEY_URL])) {
            throw new\DomainException('Version ' . $name . ' is missing required config key ' . self::KEY_URL);
        }
        if (!isset($conf[self::KEY_NAMESPACE])) {
            throw new \DomainException('Version ' . $name . ' is missing required config key ' . self::KEY_NAMESPACE);
        }
        $this->setUrl($conf[self::KEY_URL]);
        $this->setNamespace($conf[self::KEY_NAMESPACE]);
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
     * @return Version
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return Version
     */
    public function setNamespace($namespace)
    {
        if (null === $namespace) {
            $namespace = PHPFHIR_DEFAULT_NAMESPACE;
        }
        $namespace = ltrim($namespace, "\\");
        if (false === NameUtils::isValidNSName($namespace)) {
            throw new \InvalidArgumentException(sprintf(
                'Version "%s" namespace "%s" is not a valid PHP namespace.',
                $this->name,
                $this->namespace
            ));
        }
        $this->namespace = trim($namespace, "\\;");
        return $this;
    }
}