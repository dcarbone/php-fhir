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

/** @var string $copyright */
/** @var string $namespace */
/** @var string $className */
/** @var array<string, string> $fileMap fully qualified class name (no leading slash) => path relative to the profiles directory */

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $copyright; ?>


abstract class <?php echo $className; ?>

{
    /** @var array<string, string> */
    private const _CLASS_MAP = [
<?php foreach ($fileMap as $fqcn => $relativePath) : ?>
        <?php echo var_export($fqcn, true); ?> => __DIR__ . <?php echo var_export('/' . $relativePath, true); ?>,
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
