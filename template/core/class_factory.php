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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$imports = $coreFile->getImports();

$imports->addCoreFileImportsByName(
    PHPFHIR_CLASSNAME_FACTORY_CONFIG,
    PHPFHIR_INTERFACE_VERSION,
);

$coreFiles = $config->getCoreFiles();

$factConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FACTORY_CONFIG);
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo PHPFHIR_CLASSNAME_FACTORY; ?>

 *
 * This factory class exists as a helper to instantiate and manage generated Version instances.  If working within
 * a framework that provides its own service management system, it would be better to use the Version directly.
 */
final class <?php echo PHPFHIR_CLASSNAME_FACTORY; ?>

{
    private const _GENERATED_CONFIG = [
        'versions' => [
<?php foreach ($config->getVersionsIterator() as $version): ?>
            [
                'name' => '<?php echo $version->getName(); ?>',
                'class' => '<?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION); ?>',
            ],
<?php endforeach; ?>
        ]
    ];

    /** @var <?php echo $factConfigClass->getFullyQualifiedName(true); ?> */
    private static <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?> $_config;

    /** @var <?php echo $versionInterface->getFullyQualifiedName(true); ?>[] */
    private static array $_versions = [];

    private static function _init(): void
    {
        if (isset(self::$_config)) {
            return;
        }
        self::$_config = new <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>(self::_GENERATED_CONFIG);
    }

    /**
     * Set the configuration to use with this factory
     * @param array|<?php echo $factConfigClass->getFullyQualifiedName(true); ?> $config
     */
    public static function setConfig(array|<?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?> $config): void
    {
        if (is_array($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>($config);
        }
        self::$_config = new <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>($config);
    }

    /**
     * Return the current configuration used by the factory
     * @return <?php echo $factConfigClass->getFullyQualifiedName(true); ?>

     */
    public static function getConfig(): <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>

    {
        self::_init();
        return self::$_config;
    }

    /**
     * Returns Version of the specififed name.
     *
     * @param string $name Version name
     * @return <?php echo $versionInterface->getFullyQualifiedName(true); ?>

     */
    public static function getVersion(string $name): <?php echo PHPFHIR_INTERFACE_VERSION; ?>

    {
        self::_init();
        if (!isset(self::$_versions[$name])) {
            $version = self::$_config->getVersion($name);
            if (null === $version) {
                throw new \InvalidArgumentException(sprintf('Unknown version "%s"', $name));
            }
            $classname = $version->getClass();
            self::$_versions[$name] = new $classname($version->getConfig());
        }
        return self::$_versions[$name];
    }
}
<?php return ob_get_clean();
