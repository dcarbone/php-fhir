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
use DCarbone\PHPFHIR\Version\SourceMetadata;
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

    /** @var \DCarbone\PHPFHIR\Version\SourceMetadata */
    private SourceMetadata $copyright;

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
        foreach (VersionKeys::required() as $key) {
            if (!isset($params[$key->value])) {
                throw new \DomainException(sprintf(
                    'Version %s is missing required configuration key "%s"',
                    $name,
                    $key->value
                ));
            }
            $this->{"set$key->value"}($params[$key->value]);
        }

        if ((!isset($this->sourceUrl) || '' === $this->sourceUrl)) {
            throw new \DomainException(sprintf(
                'Version %s missing required configuration key "%s"',
                $name,
                VersionKeys::SOURCE_URL->value,
            ));
        }

        // attempt to set all "optional" keys
        foreach (VersionKeys::optional() as $key) {
            if (isset($params[$key->value])) {
                $this->{"set$key->value"}($params[$key->value]);
            }
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

        $this->copyright = new SourceMetadata($config, $this);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\SourceMetadata
     */
    public function getSourceMetadata(): SourceMetadata
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
     * @param string $sourceUrl
     * @return self
     */
    public function setSourceUrl(string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;
        return $this;
    }

    /**
     * Returns the specific schema path for this version's XSD's
     *
     * @return string
     */
    public function getSchemaPath(): string
    {
        return $this->config->getSchemaPath() . DIRECTORY_SEPARATOR . $this->name;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
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
     * @param string $testEndpoint
     * @return self
     */
    public function setTestEndpoint(string $testEndpoint): self
    {
        $this->testEndpoint = $testEndpoint;
        return $this;
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string...$bits): string
    {
        return $this->config->getFullyQualifiedName($leadingSlash, ...array_merge([$this->namespace], ...$bits));
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestType $testType
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(TestType $testType, bool $leadingSlash, string...$bits): string
    {
        return $this->getFullyQualifiedName($leadingSlash, $testType->namespaceSlug(), ...$bits);
    }

    /**
     * @return string
     */
    public function getVersionClassname(): string
    {
        return ucfirst($this->name);
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