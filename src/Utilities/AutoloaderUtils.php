<?php

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Definition;

/**
 * Class AutoloaderUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class AutoloaderUtils
{
    const VARS = <<<PHP
    /** @var array */
    private static \$_classMap = %s;
    /** @var bool */
    private static \$_registered = false;

PHP;

    const FUNC_REGISTER = <<<PHP
    /**
     * @return bool
     * @throws \Exception
     */
    public static function register()
    {
        if (!self::\$_registered) {
            self::\$_registered = spl_autoload_register([__CLASS__, 'loadClass'], true);
        }
        return self::\$_registered;
    }

PHP;

    const FUNC_UNREGISTER = <<<PHP
    /**
     * @return bool
     */
    public static function unregister()
    {
        if (self::\$_registered && spl_autoload_unregister([__CLASS__, 'loadClass'])) {
            self::\$_registered = false;
            return true;
        }
        return false;
    }

PHP;

    const FUNC_LOAD_CLASS = <<<PHP
    /**
     * @param string \$class
     * @return bool|null
     */
     public static function loadClass(\$class)
     {
        if (isset(self::\$_classMap[\$class])) {
            return (bool)require(__DIR__ . DIRECTORY_SEPARATOR . self::\$_classMap[\$class]);
        } else {
            return null;        
        }
     }

PHP;

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition $definition
     * @return string
     */
    public static function build(Config\VersionConfig $config, Definition $definition)
    {
        $rootNamespace = $config->getNamespace();
        $classMap = [
            "{$rootNamespace}\\PHPFHIRResponseParser" => 'PHPFHIRResponseParser',
        ];

        foreach ($definition->getTypes()->getIterator() as $type) {
            $classMap[$type->getFullyQualifiedClassName(false)] = static::buildFilePathEntry(
                $config,
                $type->getFullyQualifiedNamespace(false),
                $type->getClassName()
            );
        }

        $out = FileUtils::buildFileHeader($config->getNamespace());

        $out .= <<<PHP
/**
 * @class PHPFHIRAutoloader
PHP;
        if ('' !== $rootNamespace) {
            $out .= "\n * @package {$rootNamespace}\n";
        }

        $out .= <<<PHP
 */
class PHPFHIRAutoloader
{

PHP;

        $out .= sprintf(self::VARS, var_export($classMap, true));
        $out .= "\n";
        $out .= self::FUNC_REGISTER;
        $out .= "\n";
        $out .= self::FUNC_UNREGISTER;
        $out .= "\n";
        $out .= self::FUNC_LOAD_CLASS;
        return $out . '}';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string $classNamespace
     * @param string $classname
     * @return string
     */
    protected static function buildFilePathEntry(Config\VersionConfig $config, $classNamespace, $classname)
    {
        return ltrim(
            str_replace(
                [
                    $config->getClassesPath(),
                    str_replace('\\', '/', $config->getNamespace()),
                    '\\',
                ],
                [
                    '',
                    '',
                    '/',
                ],
                FileUtils::buildGenericClassFilePath($config, $classNamespace, $classname)
            ),
            '/\\'
        );
    }
}