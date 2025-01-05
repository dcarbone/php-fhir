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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$config = $version->getConfig();
$namespace = $version->getFullyQualifiedName(false);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


abstract class <?php echo PHPFHIR_CLASSNAME_AUTOLOADER; ?>

{
    /** @var array */
    private const _CLASS_MAP = [
        // version core types
<?php foreach($version->getCoreFiles()->getGenerator() as $coreFile): if ($coreFile->isAutoloader() || $coreFile->isTest()) { continue; } ?>
        '<?php echo $coreFile->getFullyQualifiedName(false); ?>' => <?php echo FileUtils::buildAutoloaderRelativeFilepath(
            $version->getFullyQualifiedName(false),
            $coreFile->getFullyQualifiedName(false),
        ); ?>,
<?php endforeach; ?>

        // version fhir types
<?php foreach ($version->getDefinition()->getTypes()->getNamespaceSortedIterator() as $type) : ?>
        '<?php echo $type->getFullyQualifiedClassName(false); ?>' => <?php echo FileUtils::buildAutoloaderRelativeFilepath(
            $version->getFullyQualifiedName(false),
            $type->getFullyQualifiedClassName(false),
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
        if (isset(self::_CLASS_MAP[$class])) {
            return (bool)require self::_CLASS_MAP[$class];
        }
        return null;
    }
}
<?php return ob_get_clean();