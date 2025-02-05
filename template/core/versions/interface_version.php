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

$versionConfigInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONFIG);
$versionTypeMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_TYPE_MAP);

$imports = $coreFile->getImports();

$imports->addCoreFileImports(
    $versionConfigInterface,
    $versionTypeMapInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile->getEntityName(); ?>

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
     * @return string
     */
    public function getSourceVersion(): string;

    /**
     * Must return the date this FHIR version's source was generated
     *
     * @return string
     */
    public function getSourceGenerationDate(): string;

    /**
     * Must return config for this version
     *
     * @return <?php echo $versionConfigInterface->getFullyQualifiedName(true); ?>

     */
    public function getConfig(): <?php echo $versionConfigInterface->getEntityName(); ?>;

    /**
     * Must return the type map class for this version
     *
     * @return <?php echo $versionTypeMapInterface->getFullyQualifiedName(true); ?>

     */
    public function getTypeMap(): <?php echo $versionTypeMapInterface->getEntityName(); ?>;
}
<?php return ob_get_clean();
