<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Enum\TestTypeEnum;
use RuntimeException;

/**
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
class FileUtils
{
    /**
     * @param string $path
     * @param int $dirPermMask
     */
    public static function mkdirRecurse(string $path, int $dirPermMask = 0777): void
    {
        $dirPath = pathinfo($path, PATHINFO_DIRNAME);
        if (!is_dir($dirPath) && !mkdir($dirPath, $dirPermMask, true)) {
            throw new RuntimeException(sprintf('Unable to create directory at path "%s"', $path));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $namespace
     * @return string
     */
    public static function compileNamespaceFilepath(Config $config, string $namespace): string
    {
        $base = rtrim($config->getOutputPath(), '\\/');
        $nsPath = trim(str_replace(PHPFHIR_NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace), DIRECTORY_SEPARATOR);
        return $base . DIRECTORY_SEPARATOR . $nsPath;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function buildTypeClassFilepath(Version $version, Type $type): string
    {
        return self::compileNamespaceFilepath($version->getConfig(), $type->getFullyQualifiedNamespace(false))
            . DIRECTORY_SEPARATOR
            . "{$type->getClassName()}.php";
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Enum\TestTypeEnum $testType
     * @return string
     */
    public static function buildTypeTestFilePath(Version $version, Type $type, TestTypeEnum $testType): string
    {
        return self::mkdirRecurse(
                $version->getOutputPath(),
                self::cleanupPath($type->getFullyQualifiedTestNamespace($testType, false))
            ) . DIRECTORY_SEPARATOR . "{$type->getTestClassName()}.php";
    }

    /**
     * @param string $baseNS
     * @param string $classFQN
     * @return string
     */
    public static function buildAutoloaderRelativeFilepath(string $baseNS, string $classFQN): string
    {
        $baseNS = ltrim($baseNS, '\\');
        $classFQN = ltrim($classFQN, '\\');
        if (str_starts_with($classFQN, $baseNS)) {
            $classFQN = ltrim(substr($classFQN, strlen($baseNS)), '\\');
        }
        return sprintf("__DIR__ . '/%s.php'", str_replace('\\', "/", $classFQN));
    }
}
