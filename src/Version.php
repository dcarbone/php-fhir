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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version\SourceMetadata;
use DCarbone\PHPFHIR\Version\Definition;
use DCarbone\PHPFHIR\Version\VersionDefaultConfig;

class Version
{
    private Config $_config;
    private VersionConfig $_versionConfig;

    private SourceMetadata $_sourceMetadata;

    private string $_constName;

    private Definition $_definition;

    private CoreFiles $_versionCoreFiles;
    private CoreFiles $_versionTestCoreFiles;

    public function __construct(Config              $config,
                                VersionConfig       $versionConfig,
                                null|SourceMetadata $sourceMetadata = null)
    {
        $this->_config = $config;
        $this->_versionConfig = $versionConfig;

        if (null === $sourceMetadata) {
            $sourceMetadata = new SourceMetadata($versionConfig->getSchemaPath());
        }
        $this->_sourceMetadata = $sourceMetadata;

        $this->_versionCoreFiles = new CoreFiles(
            $this->_config,
            $config->getLibraryPath(),
            PHPFHIR_TEMPLATE_VERSIONS_CORE_DIR,
            $this->getFullyQualifiedName(true),
        );
    }

    public function getName(): string
    {
        return $this->_versionConfig->getName();
    }

    public function getNamespace(): string
    {
        return $this->_versionConfig->getNamespace();
    }

    public function getSchemaPath(): string
    {
        return $this->_versionConfig->getSchemaPath();
    }

    public function getConfig(): Config
    {
        return $this->_config;
    }

    public function getVersionConfig(): VersionConfig
    {
        return $this->_versionConfig;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\SourceMetadata
     */
    public function getSourceMetadata(): SourceMetadata
    {
        return $this->_sourceMetadata;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\VersionDefaultConfig
     */
    public function getDefaultConfig(): VersionDefaultConfig
    {
        return $this->_versionConfig->getDefaultConfig();
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string ...$bits): string
    {
        return $this->_config->getFullyQualifiedName($leadingSlash, ...array_merge([$this->getNamespace()], $bits));
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(bool $leadingSlash, string ...$bits): string
    {
        return $this->_config->getFullyQualifiedTestName($leadingSlash, ...array_merge([$this->getNamespace()], $bits));
    }

    /**
     * @return string
     */
    public function getConstName(): string
    {
        if (!isset($this->_constName)) {
            $this->_constName = NameUtils::getConstName($this->getName());
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
    public function getVersionCoreFiles(): CoreFiles
    {
        return $this->_versionCoreFiles;
    }

    /**
     * @return \DCarbone\PHPFHIR\CoreFiles
     */
    public function getVersionTestCoreFiles(): CoreFiles
    {
        if (isset($this->_versionTestCoreFiles)) {
            return $this->_versionTestCoreFiles;
        }
        $tp = $this->_config->getTestsPath();
        if (null === $tp) {
            throw new \RuntimeException('No tests path has been set.');
        }
        $this->_versionTestCoreFiles = new CoreFiles(
            $this->_config,
            $tp,
            PHPFHIR_TEMPLATE_TESTS_VERSIONS_CORE_DIR,
            $this->getFullyQualifiedTestsName(false)
        );
        return $this->_versionTestCoreFiles;
    }
}
