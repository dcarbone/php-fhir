<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DomainException;
use InvalidArgumentException;

/**
 * Class Version
 * @package DCarbone\PHPFHIR\Config
 */
class Version
{
    public const KEY_URL           = 'url';
    public const KEY_NAMESPACE     = 'namespace';
    public const KEY_TEST_ENDPOINT = 'testEndpoint';

    /** @var string */
    private string $name;

    /** @var string */
    private string $url;
    /** @var string */
    private string $namespace;
    /** @var string */
    private string $testEndpoint;

    /**
     * Version constructor.
     * @param string $name
     * @param array $conf
     */
    public function __construct(string $name, array $conf = [])
    {
        $this->name = $name;

        if (!isset($conf[self::KEY_URL])) {
            throw new DomainException(sprintf('Version %s is missing required config key ', self::KEY_URL));
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function setUrl(string $url): Version
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getNamespace(bool $leadingSlash): string
    {
        return $leadingSlash ? "\\{$this->namespace}" : $this->namespace;
    }

    /**
     * @param string|null $namespace
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function setNamespace(?string $namespace): Version
    {
        if (null === $namespace) {
            $this->namespace = '';
            return $this;
        }
        // handle no or empty namespace
        $namespace = trim($namespace, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if ('' === $namespace) {
            $this->namespace = '';
            return $this;
        }

        if (false === NameUtils::isValidNSName($namespace)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Version "%s" namespace "%s" is not a valid PHP namespace.',
                    $this->name,
                    $this->namespace
                )
            );
        }

        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTestEndpoint(): ?string
    {
        return $this->testEndpoint ?? null;
    }

    /**
     * @param string $testEndpoint
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function setTestEndpoint(string $testEndpoint): Version
    {
        $this->testEndpoint = $testEndpoint;
        return $this;
    }
}