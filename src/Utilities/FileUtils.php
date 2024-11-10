<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Enum\TestType;
use RuntimeException;

/**
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class FileUtils
{
    public const REGEX_SLASH_SEARCH         = '{[\\\]}S';
    public const REGEX_SLASH_SEARCH_CLEANUP = '{[/]{2,}}S';
    public const REGEX_SLASH_REPLACE        = '/';

    /**
     * @param string ...$bits
     * @return string
     */
    public static function mkdirRecurse(string ...$bits): string
    {
        $path = '';
        foreach (preg_split('{[/\\\]}', implode('/', $bits)) as $dir) {
            $dir = trim($dir, '/');
            if ('' === $dir) {
                continue;
            }
            $path .= DIRECTORY_SEPARATOR .$dir;
            if (!is_dir($path) && !mkdir($path)) {
                throw new RuntimeException(sprintf('Unable to create directory at path "%s"', $path));
            }
        }
        return $path;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $namespace
     * @param string $filename
     * @return string
     */
    public static function buildCoreFilePath(Config $config, string $namespace, string $filename): string
    {
        return self::mkdirRecurse($config->getClassesPath(), self::cleanupPath($namespace)) . DIRECTORY_SEPARATOR . "{$filename}.php";
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function buildTypeFilePath(Version $version, Type $type): string
    {
        return static::mkdirRecurse(
                $version->getClassesPath(),
                self::cleanupPath($type->getFullyQualifiedNamespace(false))
            ) . DIRECTORY_SEPARATOR . "{$type->getClassName()}.php";
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Enum\TestType $testType
     * @return string
     */
    public static function buildTypeTestFilePath(Version $version, Type $type, TestType $testType): string
    {
        return static::mkdirRecurse(
                $version->getClassesPath(),
                self::cleanupPath($type->getFullyQualifiedTestNamespace($testType, false))
            ) . DIRECTORY_SEPARATOR . "{$type->getTestClassName()}.php";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function buildAutoloaderRelativeFilepath(Config $config, Type $type): string
    {
        return ltrim(
                str_replace(
                    [$config->getFullyQualifiedName(false), '\\'],
                    ['', '/'],
                    $type->getFullyQualifiedClassName(false)
                ),
                '/\\'
            ) . '.php';
    }

    /**
     * @param string $namespace
     * @return string
     */
    protected static function cleanupPath(string $namespace): string
    {
        $namespace = rtrim($namespace, '\\/');
        return preg_replace(
            [self::REGEX_SLASH_SEARCH, self::REGEX_SLASH_SEARCH_CLEANUP],
            self::REGEX_SLASH_REPLACE,
            $namespace
        );
    }
}
