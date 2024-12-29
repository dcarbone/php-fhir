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

use DCarbone\PHPFHIR\Utilities\FileUtils;

/** @var \DCarbone\PHPFHIR\Config $config */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


abstract class <?php echo PHPFHIR_CLASSNAME_AUTOLOADER; ?>

{
    private const _VERSION_AUTOLOADER_MAP = [
<?php foreach($config->getVersionsIterator() as $version): ?>
        '<?php echo $version->getFullyQualifiedName(false); ?>\\' => [
            '<?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_AUTOLOADER); ?>',
            <?php echo FileUtils::buildAutoloaderRelativeFilepath(
                $config->getFullyQualifiedName(false),
                $version->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_AUTOLOADER),
            ); ?>,
        ],
<?php endforeach; ?>    ];

    /** @var array */
    private const _CORE_CLASS_MAP = [
        // core types
<?php foreach($config->getCoreFiles()->getIterator() as $coreFile): if ($coreFile->isAutoloader() || $coreFile->isTest()) { continue; } ?>
        '<?php echo $coreFile->getFullyQualifiedName(false); ?>' => <?php echo FileUtils::buildAutoloaderRelativeFilepath(
        $config->getFullyQualifiedName(false),
        $coreFile->getFullyQualifiedName(false),
    ); ?>,
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
        if (isset(self::_CORE_CLASS_MAP[$class])) {
            return (bool)require self::_CORE_CLASS_MAP[$class];
        }
        foreach (self::_VERSION_AUTOLOADER_MAP as $vns => $map) {
            if (str_starts_with($class, $vns)) {
                if (!class_exists($map[0], false)) {
                    if ((bool)require $map[1]) {
                        return $map[0]::register();
                    }
                    return false;
                }
                return null;
            }
        }
        return null;
    }
}

<?php echo PHPFHIR_CLASSNAME_AUTOLOADER; ?>::register();
<?php return ob_get_clean();