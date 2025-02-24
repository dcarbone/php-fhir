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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();
$versionCoreFiles = $version->getVersionCoreFiles();

$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$versionConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_VERSION_CONFIG);
$versionConfigInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONFIG);
$versionTypeMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_TYPE_MAP);

$versionTypeMapClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP);

$imports = $coreFile->getImports();

$imports->addCoreFileImports(
    $fhirVersion,
    $versionInterface,
    $versionConfigClass,
    $versionConfigInterface,
    $versionTypeMapInterface,
    $versionTypeMapClass,
);

$sourceMeta = $version->getSourceMetadata();

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $versionInterface; ?>

{
    public const NAME = '<?php echo $version->getName(); ?>';
    public const FHIR_SEMANTIC_VERSION = '<?php echo $sourceMeta->getSemanticVersion(false); ?>';
    public const FHIR_SHORT_VERSION = '<?php echo $sourceMeta->getShortVersion(); ?>';
    public const FHIR_VERSION_INTEGER = <?php echo $sourceMeta->getVersionInteger(); ?>;
    public const FHIR_GENERATION_DATE = '<?php echo $sourceMeta->getSourceGenerationDate(); ?>';

    private const _GENERATED_CONFIG = <?php echo pretty_var_export($version->getDefaultConfig()->toArray(), 1); ?>;

    private static <?php echo $fhirVersion; ?> $_fhirVersion;

    /** @var <?php echo $versionConfigInterface->getFullyQualifiedName(true); ?> */
    private <?php echo $versionConfigInterface; ?> $_config;

    /** @var <?php echo $versionTypeMapClass->getFullyQualifiedName(true); ?> */
    private static <?php echo $versionTypeMapInterface; ?> $_typeMap;

    /**
     * <?php echo $coreFile; ?> Constructor
     * @param null|array|<?php echo $versionConfigInterface->getFullyQualifiedName(true); ?> $config
     */
    public function __construct(null|array|<?php echo $versionConfigInterface ?> $config = null)
    {
        if (!is_object($config)) {
            $config = new <?php echo $versionConfigClass; ?>(array_merge(self::_GENERATED_CONFIG, (array)$config));
        } else if (!($config instanceof <?php echo $versionConfigClass; ?>)) {
            throw new \InvalidArgumentException(sprintf(
                '$config must be an instance of \\%s, %s given',
                <?php echo $versionConfigClass; ?>::class,
                get_class($config)
            ));
        }
        $this->_config = $config;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return <?php echo $fhirVersion->getFullyQualifiedName(true); ?>

     */
    public static function getFHIRVersion(): <?php echo $fhirVersion ?>

    {
        if (!isset(self::$_fhirVersion)) {
            self::$_fhirVersion = new <?php echo $fhirVersion; ?>(
                self::NAME,
                self::FHIR_SEMANTIC_VERSION,
                self::FHIR_SHORT_VERSION,
                self::FHIR_VERSION_INTEGER,
            );
        }
        return self::$_fhirVersion;
    }

    /**
     * @return string
     */
    public function getFHIRSemanticVersion(): string
    {
        return self::FHIR_SEMANTIC_VERSION;
    }

    /**
     * @return string
     */
    public function getFHIRShortVersion(): string
    {
        return self::FHIR_SHORT_VERSION;
    }

    /**
     * @return int
     */
    public function getFHIRVersionInteger(): int
    {
        return self::FHIR_VERSION_INTEGER;
    }

    /**
     * @return string
     */
    public function getFHIRGenerationDate(): string
    {
        return self::FHIR_GENERATION_DATE;
    }

    /**
     * @return <?php echo $versionConfigInterface->getFullyQualifiedName(true); ?>

     */
    public function getConfig(): <?php echo $versionConfigInterface; ?>

    {
        return $this->_config;
    }

    /**
     * @return <?php echo $versionTypeMapClass->getFullyQualifiedName(true); ?>

     */
    public function getTypeMap(): <?php echo $versionTypeMapInterface; ?>

    {
        if (!isset(self::$_typeMap)) {
            self::$_typeMap = new <?php echo $versionTypeMapClass; ?>();
        }
        return self::$_typeMap;
    }

    public function __toString(): string
    {
        return (string)self::getFHIRVersion();
    }
}
<?php return ob_get_clean();
