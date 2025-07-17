<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$coreFiles = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$versionTypeMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_TYPE_MAP);
$versionConfigInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONFIG);

$mockVersionConfig = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_VERSION_CONFIG);
$mockVersionTypeMap = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_VERSION_TYPE_MAP);

$imports->addCoreFileImports(
    $fhirVersion,
    $versionInterface,
    $versionTypeMapInterface,
    $versionConfigInterface,

    $mockVersionConfig,
    $mockVersionTypeMap,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $versionInterface; ?>

{
    public const NAME = 'Mock';
    public const FHIR_SEMANTIC_VERSION = 'v99.99.99';
    public const FHIR_SHORT_VERSION = 'v9.9';
    public const FHIR_VERSION_INTEGER = 999999999;

    private static <?php echo $fhirVersion; ?> $_fhirVersion;
    private static <?php echo $versionTypeMapInterface; ?> $_typeMap;

    private <?php echo $versionConfigInterface; ?> $_config;

    public function __construct(null|<?php echo $versionConfigInterface; ?> $config = null)
    {
        $this->_config = $config ?? new <?php echo $mockVersionConfig; ?>();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public static function getFHIRVersion(): <?php echo $fhirVersion; ?>
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

    public function getFHIRSemanticVersion(): string
    {
        return self::FHIR_SEMANTIC_VERSION;
    }

    public function getFHIRShortVersion(): string
    {
        return self::FHIR_SHORT_VERSION;
    }

    public function getFHIRVersionInteger(): int
    {
        return self::FHIR_VERSION_INTEGER;
    }

    public function getFHIRGenerationDate(): string
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function getConfig(): <?php echo $versionConfigInterface; ?>
    {
        return $this->_config;
    }

    public function getTypeMap(): <?php echo $versionTypeMapInterface; ?>
    {
        if (!isset(self::$_typeMap)) {
            self::$_typeMap = new <?php echo $mockVersionTypeMap; ?>();
        }
        return self::$_typeMap;
    }
}
<?php return ob_get_clean();
