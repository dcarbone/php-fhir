<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\FileUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version\SourceMetadata;
use DCarbone\PHPFHIR\Version\Definition;
use DCarbone\PHPFHIR\Version\DefaultConfig;

class Version
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $_config;
    /** @var string */
    private string $_schemaPath;

    /** @var \DCarbone\PHPFHIR\Version\SourceMetadata */
    private SourceMetadata $_sourceMetadata;

    /** @var \DCarbone\PHPFHIR\Version\DefaultConfig */
    private DefaultConfig $_defaultConfig;

    /** @var string */
    private string $_name;
    /** @var string */
    private string $_namespace;
    /** @var string */
    private string $_constName;

    /** @var \DCarbone\PHPFHIR\Version\Definition */
    private Definition $_definition;

    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_coreFiles;
    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_versionTestFiles;
    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_typesTestFiles;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $name
     * @param string $schemaPath
     * @param null|string $namespace
     * @param null|array|\DCarbone\PHPFHIR\Version\DefaultConfig $defaultConfig
     */
    public function __construct(Config                   $config,
                                string                   $name,
                                string                   $schemaPath,
                                null|string              $namespace = null,
                                null|array|DefaultConfig $defaultConfig = null)
    {
        $this->_config = $config;
        $this->_name = $name;
        $this->setSchemaPath($schemaPath);

        if ('' === trim($this->_name)) {
            throw new \DomainException('Version name cannot be empty.');
        }

        // if no specific namespace is set, try to use the name
        if (null === $namespace) {
            if (!NameUtils::isValidNSName($name)) {
                throw new \InvalidArgumentException(sprintf(
                    'Version namespace not set and version name "%s" is not a valid PHP namespace value.  Please set a namespace value, or change the name.',
                    $name
                ));
            }
            $namespace = $name;
        }

        $this->setNamespace($namespace);

        if (!is_object($defaultConfig)) {
            $defaultConfig = new Version\DefaultConfig((array)$defaultConfig);
        }
        $this->setDefaultConfig($defaultConfig);

        $this->_sourceMetadata = new SourceMetadata($config, $this);

        $this->_coreFiles = new CoreFiles(
            $this->_config,
            $config->getLibraryPath(),
            PHPFHIR_TEMPLATE_VERSIONS_CORE_DIR,
            $this->getFullyQualifiedName(true),
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
    public function getSchemaPath(): string
    {
        return $this->_schemaPath;
    }

    /**
     * @param string $schemaPath
     * @return self
     */
    public function setSchemaPath(string $schemaPath): self
    {
        if (!is_dir($schemaPath) || !is_readable($schemaPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Specified schema path "%s" either does not exist or is not readable',
                $schemaPath
            ));
        }
        $this->_schemaPath = $schemaPath;
        return $this;
    }

    /**
     * Returns the specific class output path for this version's generated code
     *
     * @return string
     */
    public function getOutputPath(): string
    {
        return FileUtils::compileNamespaceFilepath($this->_config, $this->_namespace);
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
        // ensure namespace is valid
        if (!NameUtils::isValidNSName($namespace)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP namespace.', $namespace));
        }
        $this->_namespace = PHPFHIR_NAMESPACE_VERSIONS . PHPFHIR_NAMESPACE_SEPARATOR . $namespace;
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
     * @return self
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
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(bool $leadingSlash, string ...$bits): string
    {
        return $this->_config->getFullyQualifiedTestName($leadingSlash, ...array_merge([$this->_namespace], $bits));
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
            $this->_definition = new Definition($this);
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

    /**
     * @return \DCarbone\PHPFHIR\CoreFiles
     */
    public function getVersionTestFiles(): CoreFiles
    {
        if (isset($this->_versionTestFiles)) {
            return $this->_versionTestFiles;
        }
        $tp = $this->_config->getTestsPath();
        if (null === $tp) {
            throw new \RuntimeException('No tests path has been set.');
        }
        $this->_versionTestFiles = new CoreFiles(
            $this->_config,
            $tp,
            PHPFHIR_TEMPLATE_TESTS_VERSIONS_CORE_DIR,
            $this->getFullyQualifiedTestsName(false)
        );
        return $this->_versionTestFiles;
    }

    /**
     * @return \DCarbone\PHPFHIR\CoreFiles
     */
    public function getTypesTestFiles(): CoreFiles
    {
        if (isset($this->_typesTestFiles)) {
            return $this->_typesTestFiles;;
        }
        $tp = $this->_config->getTestsPath();
        if (null === $tp) {
            throw new \RuntimeException('No tests path has been set.');
        }
        $this->_typesTestFiles = new CoreFiles(
            $this->_config,
            $tp,
            PHPFHIR_TEMPLATE_TESTS_VERSIONS_TYPES_DIR,
            $this->getFullyQualifiedTestsName(false, PHPFHIR_NAMESPACE_TYPES)
        );
        return $this->_typesTestFiles;
    }
}