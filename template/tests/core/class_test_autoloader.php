<?php declare(strict_types=1);

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

use DCarbone\PHPFHIR\Utilities\FileUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreTestFiles = $config->getCoreTestFiles();

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

abstract class <?php echo $coreFile; ?>

{
    private const _CLASS_MAP = [
        // core types
<?php foreach($coreTestFiles->getIterator() as $testCoreFile): if ($testCoreFile->isAutoloader()) { continue; } ?>
        '<?php echo $testCoreFile->getFullyQualifiedName(false); ?>' => <?php echo FileUtils::buildAutoloaderRelativeFilepath(
        $config->getFullyQualifiedTestName(false),
        $testCoreFile->getFullyQualifiedName(false),
    ); ?>,
<?php endforeach; ?>    ];

    private static bool $_registered = false;

    public static function register(): bool
    {
        if (!self::$_registered) {
            self::$_registered = spl_autoload_register(__CLASS__ . '::loadClass');
        }
        return self::$_registered;
    }

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

    public static function loadClass(string $class): null|bool
    {
        if (isset(self::_CLASS_MAP[$class])) {
            return (bool)require self::_CLASS_MAP[$class];
        }
        return null;
    }
}

<?php echo $coreFile; ?>::register();
<?php return ob_get_clean();
