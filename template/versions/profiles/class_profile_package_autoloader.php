<?php declare(strict_types=1);

/*
 * Copyright 2018-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var string $copyright */
/** @var string $namespace */
/** @var string $className */
/** @var string[] $classes fully qualified names, without leading slash, of the classes beneath $namespace */
/** @var string $interface fully qualified name, without leading slash, of the package autoloader interface */

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use <?php echo $interface; ?>;

<?php echo $copyright; ?>


abstract class <?php echo $className; ?> implements <?php echo NameUtils::splitFullyQualifiedName($interface)[1]; ?>

{
    /** @var array<string, string> */
    private const _CLASS_MAP = [
<?php foreach ($classes as $fqcn) : ?>
        <?php echo var_export($fqcn, true); ?> => <?php echo FileUtils::buildAutoloaderRelativeFilepath($namespace, $fqcn); ?>,
<?php endforeach; ?>
    ];

    /** @var bool */
    private static bool $_registered = false;

    /**
     * @return bool
     */
    public static function register(): bool
    {
        if (!self::$_registered) {
            self::$_registered = spl_autoload_register(self::class . '::loadClass', true);
        }
        return self::$_registered;
    }

    /**
     * @return bool
     */
    public static function unregister(): bool
    {
        if (self::$_registered && spl_autoload_unregister(self::class . '::loadClass')) {
            self::$_registered = false;
            return true;
        }
        return false;
    }

    /**
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
