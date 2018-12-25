<?php

namespace DCarbone\PHPFHIR\Utilities;

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
            self::\$_registered = spl_autoload_register(array(__CLASS__, 'loadClass'), true);
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
    public static function buildAutoloader(Config\VersionConfig $config, Definition $definition)
    {
        $classMap = [];
        $classesPath = $config->getClassesPath();
        $rootNamespace = str_replace('\\', '/', $config->getNamespace());

        foreach ($definition->getTypes()->getIterator() as $type) {
            $classMap[$type->getFullyQualifiedClassName(false)] = ltrim(
                str_replace(
                    [$classesPath, $rootNamespace, '\\'],
                    ['', '', '/'],
                    FileUtils::buildTypeFilePath($config, $type)
                ),
                '/\\'
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
}