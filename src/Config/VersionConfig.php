<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Version\VersionDefaultConfig;

/**
 * Class containing purely version-specific configuration attributes.  If you forget why you have it this way in the
 * future, its because otherwise you have a cyclical dependency loop between the full Version instance and Config.
 *
 * Config can construct Version, but Version needs Config to be constructed.  Need to do better here.
 */
class VersionConfig
{
    private string $_name;
    private string $_schemaPath;
    private string $_namespace;
    private VersionDefaultConfig $_defaultConfig;

    public function __construct(string                          $name,
                                string                          $schemaPath,
                                null|string                     $namespace = null,
                                null|array|VersionDefaultConfig $defaultConfig = null)
    {
        // ensure non-empty name
        if ('' === trim($name)) {
            throw new \DomainException('Version name cannot be empty.');
        }
        $this->_name = $name;

        // ensure schema path actually exists.
        if (!is_dir($schemaPath) || !is_readable($schemaPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Specified schema path "%s" either does not exist or is not readable',
                $schemaPath
            ));
        }
        $this->_schemaPath = $schemaPath;

        if (null === $namespace) {
            $namespace = $name;
        }
        // ensure namespace is valid
        if (!NameUtils::isValidNSName($namespace)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP namespace.', $namespace));
        }
        $this->_namespace = PHPFHIR_NAMESPACE_VERSIONS . PHPFHIR_NAMESPACE_SEPARATOR . $namespace;

        // construct default config instance.
        if (!($defaultConfig instanceof VersionDefaultConfig)) {
            $defaultConfig = VersionDefaultConfig::fromArray((array)$defaultConfig);
        }
        $this->_defaultConfig = $defaultConfig;
    }

    public static function fromArray(array $config): self
    {
        if (!isset($config['name'])) {
            throw new \InvalidArgumentException('Version name is required');
        }
        if (!isset($config['schemaPath'])) {
            throw new \InvalidArgumentException('Path to schemas for version is required');
        }
        return new VersionConfig(
            name: $config['name'],
            schemaPath: $config['schemaPath'],
            namespace: $config['namespace'] ?? null,
            defaultConfig: $config['defaultConfig'] ?? null,
        );
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getSchemaPath(): string
    {
        return $this->_schemaPath;
    }

    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function getDefaultConfig(): VersionDefaultConfig
    {
        return $this->_defaultConfig;
    }
}
