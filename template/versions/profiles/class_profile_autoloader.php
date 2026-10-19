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
/** @var array<int, array{namespace: string, autoloader: string, path: string}> $packages */

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

    /** @var array<int, array{0: string, 1: string, 2: string}> package namespace prefix, autoloader class, autoloader path */
    private const _PACKAGE_AUTOLOADER_MAP = [
<?php foreach ($packages as $package) : ?>
        [<?php echo var_export($package['namespace'] . '\\', true); ?>, <?php echo var_export($package['autoloader'], true); ?>, __DIR__ . <?php echo var_export('/' . $package['path'], true); ?>],
<?php endforeach; ?>
    ];

    /** @var bool */
    private static bool $_registered = false;

    /** @var array<int, bool> */
    private static array $_packageRegistered = [
<?php foreach ($packages as $i => $package) : ?>
        <?php echo $i; ?> => false,
<?php endforeach; ?>
    ];

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
     * Unregisters this autoloader along with the package autoloaders it registered.
     *
     * @return bool
     */
    public static function unregister(): bool
    {
        if (!self::$_registered || !spl_autoload_unregister(self::class . '::loadClass')) {
            return false;
        }
        self::$_registered = false;
        foreach (self::_PACKAGE_AUTOLOADER_MAP as $i => [, $autoloader]) {
            /** @var class-string<PackageAutoloaderInterface> $autoloader */
            if (self::$_packageRegistered[$i]) {
                $autoloader::unregister();
                self::$_packageRegistered[$i] = false;
            }
        }
        return true;
    }

    /**
     * Loads the autoloader of the package a class belongs to the first time one of its classes is asked for.  Package
     * namespaces may be nested, so every package whose namespace the class is in is tried until one loads it.  Packages
     * registered earlier are skipped, as their own autoloaders already had their turn.
     *
     * @param string $class
     * @return bool|null
     */
    public static function loadClass(string $class): null|bool
    {
        if (isset(self::_CLASS_MAP[$class])) {
            return (bool)require self::_CLASS_MAP[$class];
        }
        foreach (self::_PACKAGE_AUTOLOADER_MAP as $i => [$prefix, $autoloader, $path]) {
            /** @var class-string<PackageAutoloaderInterface> $autoloader */
            if (self::$_packageRegistered[$i] || !str_starts_with($class, $prefix)) {
                continue;
            }
            // the autoloader is already loaded if this one was registered before.
            if (!class_exists($autoloader, false)) {
                require $path;
            }
            $autoloader::register();
            self::$_packageRegistered[$i] = true;
            if ($class === $autoloader || $autoloader::loadClass($class)) {
                return true;
            }
        }
        return null;
    }
}
<?php return ob_get_clean();
