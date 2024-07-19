<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Enum\TestType;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version\VersionCopyright;
use DCarbone\PHPFHIR\Version\Definition;
use InvalidArgumentException;

/**
 * Class Version
 * @package DCarbone\PHPFHIR\Config
 */
class Version
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $config;

    /** @var \DCarbone\PHPFHIR\Version\VersionCopyright */
    private VersionCopyright $copyright;

    /** @var string */
    private string $name;
    /** @var string */
    private string $sourceUrl;
    /** @var string */
    private string $sourcePath;
    /** @var string */
    private string $namespace;
    /** @var string */
    private string $testEndpoint;

    /** @var \DCarbone\PHPFHIR\Version\Definition */
    private Definition $definition;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $name
     * @param array $params
     */
    public function __construct(Config $config, string $name, array $params = [])
    {
        $this->config = $config;
        $this->name = $name;

        if ('' === trim($this->name)) {
            throw new \DomainException('Version name cannot be empty.');
        }

        // attempt to set each required key
        foreach(VersionKeys::required() as $key) {
            if (!isset($params[$key->value])) {
                throw new \DomainException(sprintf('Missing required configuration key "%s"', $key->value));
            }
            $this->{"set$key->value"}($params[$key->value]);
        }

        // attempt to set all "optional" keys
        foreach(VersionKeys::optional() as $key) {
            if (isset($params[$key->value])) {
                $this->{"set$key->value"}($params[$key->value]);
            }
        }

        if ((!isset($this->sourceUrl) || '' === $this->sourceUrl) || (!isset($this->sourcePath) || '' === $this->sourcePath)) {
            throw new \DomainException(sprintf(
                'Must configure "%s" and / or "%s" per version',
                VersionKeys::SOURCE_URL->value,
                VersionKeys::SOURCE_PATH->value,
            ));
        }

        // ensure namespace is valid
        if (!NameUtils::isValidNSName($this->namespace)) {
            throw new InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid PHP namespace.',
                    $this->namespace
                )
            );
        }

        $this->copyright = new VersionCopyright($config, $this);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\VersionCopyright
     */
    public function getCopyright(): VersionCopyright
    {
        return $this->copyright;
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
    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     * @return $this
     */
    public function setSourcePath(string $sourcePath): self
    {
        // Bunch'o validation
        if (false === is_dir($sourcePath)) {
            throw new \RuntimeException('Unable to locate XSD dir "' . $sourcePath . '"');
        }
        if (false === is_readable($sourcePath)) {
            throw new \RuntimeException('This process does not have read access to directory "' . $sourcePath . '"');
        }
        $this->sourcePath = rtrim($sourcePath, "/\\");
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTestEndpoint(): string|null
    {
        return $this->testEndpoint ?? null;
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string... $bits): string
    {
        return $this->config->getFullyQualifiedName($leadingSlash, ...array_merge([$this->namespace], ...$bits));
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
     * @return \DCarbone\PHPFHIR\Version\Definition
     */
    public function getDefinition(): Definition
    {
        if (!isset($this->definition)) {
            $this->definition = new Definition($this->config, $this);
        }
        return $this->definition;
    }
}