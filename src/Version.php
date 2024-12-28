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

use Composer\Semver\Semver;
use DCarbone\PHPFHIR\Enum\TestTypeEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version\SourceMetadata;
use DCarbone\PHPFHIR\Version\Definition;
use DCarbone\PHPFHIR\Version\DefaultConfig;

/**
 * Class Version
 * @package DCarbone\PHPFHIR\Config
 */
class Version
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $_config;

    /** @var \DCarbone\PHPFHIR\Version\SourceMetadata */
    private SourceMetadata $_sourceMetadata;

    /** @var \DCarbone\PHPFHIR\Version\DefaultConfig */
    private DefaultConfig $_defaultConfig;

    /** @var string */
    private string $_name;
    /** @var string */
    private string $_sourceUrl;
    /** @var string */
    private string $_namespace;
    /** @var string */
    private string $_constName;

    /** @var \DCarbone\PHPFHIR\Version\Definition */
    private Definition $_definition;

    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_coreFiles;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $name
     * @param array $params
     */
    public function __construct(Config $config, string $name, array $params = [])
    {
        $this->_config = $config;
        $this->_name = $name;

        if ('' === trim($this->_name)) {
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

        if ((!isset($this->_sourceUrl) || '' === $this->_sourceUrl)) {
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
        if (!NameUtils::isValidNSName($this->_namespace)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid PHP namespace.',
                    $this->_namespace
                )
            );
        }

        if (!isset($this->_defaultConfig)) {
            $this->_defaultConfig = new DefaultConfig([]);
        }

        $this->_sourceMetadata = new SourceMetadata($config, $this);

        $this->_coreFiles = new CoreFiles(
            $config->getOutputPath(),
            PHPFHIR_TEMPLATE_VERSIONS_CORE_DIR,
            $this->getFullyQualifiedName(true),
            $this->getFullyQualifiedTestsName(TestTypeEnum::BASE, true)
        );
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig(): Config
    {
        return $this->_config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\SourceMetadata
     */
    public function getSourceMetadata(): SourceMetadata
    {
        return $this->_sourceMetadata;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * @return string
     */
    public function getSourceUrl(): string
    {
        return $this->_sourceUrl;
    }

    /**
     * @param string $sourceUrl
     * @return self
     */
    public function setSourceUrl(string $sourceUrl): self
    {
        $this->_sourceUrl = $sourceUrl;
        return $this;
    }

    /**
     * Returns the specific schema path for this version's XSD's
     *
     * @return string
     */
    public function getSchemaPath(): string
    {
        return $this->_config->getSchemaPath() . DIRECTORY_SEPARATOR . $this->_name;
    }

    /**
     * Returns the specific class output path for this version's generated code
     *
     * @return string
     */
    public function getClassesPath(): string
    {
        return $this->_config->getOutputPath();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    /**
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->_namespace = $namespace;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\DefaultConfig
     */
    public function getDefaultConfig(): DefaultConfig
    {
        return $this->_defaultConfig;
    }

    /**
     * @param array|\DCarbone\PHPFHIR\Version\DefaultConfig $defaultConfig
     * @return $this
     */
    public function setDefaultConfig(array|DefaultConfig $defaultConfig): self
    {
        if (is_array($defaultConfig)) {
            $defaultConfig = new DefaultConfig($defaultConfig);
        }
        $this->_defaultConfig = $defaultConfig;
        return $this;
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string ...$bits): string
    {
        return $this->_config->getFullyQualifiedName($leadingSlash, ...array_merge([$this->_namespace], $bits));
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestTypeEnum $testType
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(TestTypeEnum $testType, bool $leadingSlash, string ...$bits): string
    {
        return $this->getFullyQualifiedName($leadingSlash, $testType->namespaceSlug(), ...$bits);
    }

    /**
     * @return string
     */
    public function getConstName(): string
    {
        if (!isset($this->_constName)) {
            $this->_constName = NameUtils::getConstName($this->_name);
        }
        return $this->_constName;
    }

    /**
     * @return string
     */
    public function getEnumImportName(): string
    {
        return sprintf('Version%s', $this->getConstName());
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition
     */
    public function getDefinition(): Definition
    {
        if (!isset($this->_definition)) {
            $this->_definition = new Definition($this->_config, $this);
        }
        return $this->_definition;
    }

    /**
     * @return \DCarbone\PHPFHIR\CoreFiles
     */
    public function getCoreFiles(): CoreFiles
    {
        return $this->_coreFiles;
    }
}