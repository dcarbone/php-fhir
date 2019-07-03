<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class FileUtils
{
    const REGEX_SLASH_SEARCH         = '{[\\\]}S';
    const REGEX_SLASH_SEARCH_CLEANUP = '{[/]{2,}}S';
    const REGEX_SLASH_REPLACE        = '/';

    /**
     * @param string $namespace
     * @return string
     */
    public static function buildFileHeader($namespace)
    {
        $out = "<?php\n\n";
        $namespace = trim($namespace, " \t\n\r\0\x0b\\/");
        if ('' !== $namespace) {
            $out .= "namespace {$namespace};\n\n";
        }
        $out .= CopyrightUtils::getFullPHPFHIRCopyrightComment();
        return $out . "\n\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string $pathSuffix
     * @return string
     */
    public static function mkdirRecurse(VersionConfig $config, $pathSuffix)
    {
        $path = $config->getClassesPath();
        foreach (explode('/', str_replace('\\', '/', $pathSuffix)) as $dir) {
            $dir = trim($dir, "/");
            if ('' === $dir) {
                continue;
            }
            $path .= "/{$dir}";
            if (is_dir($path)) {
                $config->getLogger()->debug(sprintf('Directory at path "%s" already exists.', $path));
            } else {
                $config->getLogger()->info(sprintf('Attempting to create directory at path "%s"...', $path));
                if (!(bool)mkdir($path)) {
                    $msg = 'Unable to create directory at path "' . $path . '"';
                    $config->getLogger()->critical($msg);
                    throw new \RuntimeException($msg);
                }
            }
        }
        return $path;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string $namespace
     * @param string $filename
     * @return string
     */
    public static function buildGenericFilePath(VersionConfig $config, $namespace, $filename)
    {
        return self::mkdirRecurse($config, self::cleanupPath($namespace)) . "/{$filename}.php";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildTypeFilePath(VersionConfig $config, Type $type)
    {
        return static::mkdirRecurse(
                $config,
                self::cleanupPath($type->getFullyQualifiedNamespace(false))
            ) . "/{$type->getClassName()}.php";
    }

    /**
     * @param string $namespace
     * @return string
     */
    protected static function cleanupPath($namespace)
    {
        $namespace = rtrim($namespace, '\\/');
        return preg_replace(
            [self::REGEX_SLASH_SEARCH, self::REGEX_SLASH_SEARCH_CLEANUP],
            self::REGEX_SLASH_REPLACE,
            $namespace
        );
    }
}