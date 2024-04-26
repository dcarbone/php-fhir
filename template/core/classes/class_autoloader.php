<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$nsPrefix = "{$namespace}\\";

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>
// if this class is used, assume not using Composer...

// interfaces
if (!interface_exists('\<?php echo $nsPrefix . PHPFHIR_INTERFACE_TYPE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_TYPE; ?>.php';
}
if (!interface_exists('\<?php echo $nsPrefix . PHPFHIR_INTERFACE_PRIMITIVE_TYPE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_PRIMITIVE_TYPE; ?>.php';
}
if (!interface_exists('\<?php echo $nsPrefix . PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>.php';
}
if (!interface_exists('\<?php echo $nsPrefix . PHPFHIR_INTERFACE_COMMENT_CONTAINER; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_COMMENT_CONTAINER; ?>.php';
}
if (!interface_exists('\<?php echo $nsPrefix . PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>.php';
}
if (!interface_exists('\<?php echo $nsPrefix . PHPFHIR_INTERFACE_XML_SERIALIZABLE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE; ?>.php';
}

// traits
if (!trait_exists('\<?php echo $nsPrefix . PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>.php';
}
if (!trait_exists('\<?php echo $nsPrefix . PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; ?>.php';
}
if (!trait_exists('\<?php echo $nsPrefix . PHPFHIR_TRAIT_CHANGE_TRACKING; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>.php';
}
if (!trait_exists('\<?php echo $nsPrefix . PHPFHIR_TRAIT_XMLNS; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_TRAIT_XMLNS; ?>.php';
}
if (!trait_exists('\<?php echo $nsPrefix . PHPFHIR_TRAIT_XML_SERIALIZABLE_CONFIG; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_TRAIT_XML_SERIALIZABLE_CONFIG; ?>.php';
}

// enums
if (!enum_exists('\<?php echo $nsPrefix . PHPFHIR_ENUM_CONFIG_KEY; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>.php';
}
if (!enum_exists('\<?php echo $nsPrefix . PHPFHIR_ENUM_TYPE; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_ENUM_TYPE; ?>.php';
}
if (!enum_exists('\<?php echo $nsPrefix . PHPFHIR_ENUM_API_FORMAT; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_ENUM_API_FORMAT; ?>.php';
}

// classes
if (!class_exists('\<?php echo $nsPrefix . PHPFHIR_CLASSNAME_CONSTANTS; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>.php';
}
if (!class_exists('\<?php echo $nsPrefix . PHPFHIR_CLASSNAME_TYPEMAP; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>.php';
}
if (!class_exists('\<?php echo $nsPrefix . PHPFHIR_CLASSNAME_CONFIG; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_CONFIG; ?>.php';
}
if (!class_exists('\<?php echo $nsPrefix . PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>.php';
}
if (!class_exists('\<?php echo $nsPrefix . PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?>', false)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '<?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?>.php';
}

/**
 * Class <?php echo PHPFHIR_CLASSNAME_AUTOLOADER; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class <?php echo PHPFHIR_CLASSNAME_AUTOLOADER; ?>

{
    /** @var array */
    private const _CLASS_MAP = [
<?php foreach ($types->getNamespaceSortedIterator() as $type) : ?>
        '<?php echo $type->getFullyQualifiedClassName(false); ?>' => '<?php echo FileUtils::buildAutoloaderRelativeFilepath($config, $type); ?>',
<?php endforeach; ?>    ];

    /** @var bool */
    private static bool $_registered = false;

    /**
     * @return bool
     * @throws \Exception
     */
    public static function register(): bool
    {
        if (!self::$_registered) {
            self::$_registered = spl_autoload_register(__CLASS__ . '::loadClass', true);
        }
        return self::$_registered;
    }

    /**
     * @return bool
     */
    public static function unregister(): bool
    {
        if (self::$_registered) {
            if (spl_autoload_unregister(__CLASS__ . '::loadClass')) {
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
    public static function loadClass(string $class): null|bool
    {
        if (isset(self::_CLASS_MAP[$class])) {
            return (bool)require __DIR__ . DIRECTORY_SEPARATOR . self::_CLASS_MAP[$class];
        }
        return null;
    }
}
<?php return ob_get_clean();