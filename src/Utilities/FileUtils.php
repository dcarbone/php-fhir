<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use InvalidArgumentException;
use RuntimeException;

/**
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
class FileUtils
{
    public static function assertDirReadableAndWriteable(string $path): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to locate directory "%s"', $path));
        }
        if (!is_writable($path)) {
            throw new \RuntimeException(
                sprintf(
                    'Specified directory "%s" is not writable by this process.',
                    $path
                )
            );
        }
        if (!is_readable($path)) {
            throw new \RuntimeException(
                sprintf(
                    'Specified directory "%s" is not readable by this process.',
                    $path
                )
            );
        }
    }

    /**
     * @param string $path
     * @param string $what Description of the path for the error message, e.g. 'Schema path'.
     * @throws \InvalidArgumentException If the path is not a readable file or directory.
     */
    public static function assertPathReadable(string $path, string $what): void
    {
        if ('' === trim($path) || !file_exists($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('%s "%s" either does not exist or is not readable.', $what, $path));
        }
        if (!is_dir($path) && !is_file($path)) {
            throw new InvalidArgumentException(sprintf('%s "%s" must be a file or directory.', $what, $path));
        }
    }

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
     * @param string $basepath
     * @param string $namespace
     * @return string
     */
    public static function compileNamespaceFilepath(string $basepath, string $namespace): string
    {
        $basepath = rtrim($basepath, '\\/');
        $nsPath = trim(str_replace(PHPFHIR_NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace), DIRECTORY_SEPARATOR);
        return $basepath . DIRECTORY_SEPARATOR . $nsPath;
    }

    /**
     * @param string $basepath
     * @param string $classFQN Fully qualified class name, with or without leading slash.
     * @return string Path of the file the class is written to beneath $basepath.
     */
    public static function buildClassFilepath(string $basepath, string $classFQN): string
    {
        [$namespace, $className] = NameUtils::splitFullyQualifiedName($classFQN);
        return rtrim(self::compileNamespaceFilepath($basepath, $namespace), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . "{$className}.php";
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function buildTypeClassFilepath(Version $version, Type $type): string
    {
        return self::buildClassFilepath($version->getConfig()->getLibraryPath(), $type->getFullyQualifiedClassName(false));
    }

    public static function buildTypeTestClassFilepath(Version $version, Type $type): string
    {
        return self::compileNamespaceFilepath($version->getConfig()->getTestsPath(), $type->getFullyQualifiedTestNamespace(false))
            . DIRECTORY_SEPARATOR
            . "{$type->getTestClassName()}.php";
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
