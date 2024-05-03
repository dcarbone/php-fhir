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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition;
use DCarbone\PHPFHIR\Enum\TestType;
use DCarbone\PHPFHIR\Logger;

/**
 * Class VersionConfig
 * @package DCarbone\PHPFHIR\Config
 */
class VersionConfig
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $config;
    /** @var \DCarbone\PHPFHIR\Config\Version */
    private Version $version;

    /** @var \DCarbone\PHPFHIR\Definition */
    private Definition $definition;

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
    public function getLogger(): Logger
    {
        return $this->config->getLogger();
    }

    /**
     * @return string
     */
    public function getSchemaPath(): string
    {
        return "{$this->config->getSchemaPath()}/{$this->version->getName()}";
    }

    /**
     * @return string
     */
    public function getClassesPath(): string
    {
        return $this->config->getClassesPath();
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->version->getUrl();
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string... $bits): string
    {
        $ns = $this->version->getNamespace($leadingSlash);
        $bits = array_filter($bits);
        if ([] === $bits) {
            return $ns;
        }
        return sprintf('%s\\%s', $ns, implode('\\' , $bits));
    }

    /**
     * @return bool
     */
    public function isSkipTests(): bool
    {
        return $this->config->isSkipTests();
    }

    /**
     * @return int|null
     */
    public function getLibxmlOpts(): ?int
    {
        return $this->config->getLibxmlOpts();
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestType $testType
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(TestType $testType, bool $leadingSlash, string... $bits): string
    {
        return $this->getFullyQualifiedName($leadingSlash, $testType->namespaceSlug(), ...$bits);
    }

    /**
     * @return string|null
     */
    public function getTestEndpoint(): ?string
    {
        return $this->version->getTestEndpoint();
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition
     */
    public function getDefinition(): Definition
    {
        if (!isset($this->definition)) {
            $this->definition = new Definition($this);
        }
        return $this->definition;;
    }
}