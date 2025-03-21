<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$imports = $coreFile->getImports();

$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$versionConfigInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONFIG);
$versionTypeMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_TYPE_MAP);

$imports->addCoreFileImports(
    $fhirVersion,
    $versionConfigInterface,
    $versionTypeMapInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?>

{
    /**
     * Must return the "name" of this version.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Must return source's reported version of FHIR
     *
     * @return <?php echo $fhirVersion->getFullyQualifiedName(true); ?>

     */
    public static function getFHIRVersion(): <?php echo $fhirVersion ?>;

    /**
     * Must return source's reported version of FHIR
     *
     * @return string
     */
    public function getFHIRSemanticVersion(): string;

    /*
     * Must return the shortened Major.Minor representation of the source's semantic version.
     *
     * @return string
     */
    public function getFHIRShortVersion(): string;

    /**
     * Must return an integer representation of the source's semantic version.
     *
     * @return int
     */
    public function getFHIRVersionInteger(): int;

    /**
     * Must return the date this FHIR version's source was generated
     *
     * @return string
     */
    public function getFHIRGenerationDate(): string;

    /**
     * Must return config for this version
     *
     * @return <?php echo $versionConfigInterface->getFullyQualifiedName(true); ?>

     */
    public function getConfig(): <?php echo $versionConfigInterface; ?>;

    /**
     * Must return the type map class for this version
     *
     * @return <?php echo $versionTypeMapInterface->getFullyQualifiedName(true); ?>

     */
    public function getTypeMap(): <?php echo $versionTypeMapInterface; ?>;
}
<?php return ob_get_clean();
