<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\FileUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>
// if this class is used, assume not using Composer...

// interfaces
if (!interface_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_TYPE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_TYPE; ?>.php';
}
if (!interface_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>.php';
}
if (!interface_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_COMMENT_CONTAINER; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_COMMENT_CONTAINER; ?>.php';
}

// traits
if (!trait_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>.php';
}

// common classes
if (!class_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_CLASSNAME_CONSTANTS; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>.php';
}
if (!class_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_CLASSNAME_TYPEMAP; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>.php';
}
if (!class_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>.php';
}
if (!class_exists('\<?php echo (null !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>.php';
}

/**
 * Class <?php echo PHPFHIR_CLASSNAME_AUTOLOADER; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class <?php echo PHPFHIR_CLASSNAME_AUTOLOADER; ?>

{
    const ROOT_DIR = __DIR__;

    /** @var bool */
    private static $_registered = false;

    /** @var array */
    private static $_classMap = [
<?php foreach ($types->getNamespaceSortedIterator() as $type) : ?>
        '<?php echo $type->getFullyQualifiedClassName(false); ?>' => '<?php echo FileUtils::buildAutoloaderRelativeFilepath($config, $type); ?>',
<?php endforeach; ?>    ];

    /**
     * @return bool
     * @throws \Exception
     */
    public static function register()
    {
        if (self::$_registered) {
            return self::$_registered;
        }
        return self::$_registered = spl_autoload_register(array(__CLASS__, 'loadClass'), true);
    }

    /**
     * @return bool
     */
    public static function unregister()
    {
        if (self::$_registered) {
            if (spl_autoload_unregister(array(__CLASS__, 'loadClass'))) {
                self::$_registered = false;
                return true;
            }
        }
        return false;
    }

    /**
     * Please see associated documentation for more information on what this method looks for.
     *
     * @param string $class
     * @return bool|null
     */
    public static function loadClass($class)
    {
        if (isset(self::$_classMap[$class])) {
            return (bool)require sprintf('%s/%s', self::ROOT_DIR, self::$_classMap[$class]);
        }
        return null;
    }
}
<?php return ob_get_clean();