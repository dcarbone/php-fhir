<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$imports->addCoreFileImport($fhirVersion);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?> extends \JsonSerializable
{
    /**
     * Returns the FHIR name represented by this Type
     *
     * @return string
     */
    public function _getFHIRTypeName(): string;

    /**
     * Must return the FHIR version of this type.
     *
     * @return <?php echo $fhirVersion->getFullyQualifiedName(true); ?>

     */
    public function _getFHIRVersion(): <?php echo $fhirVersion; ?>;

    /**
     * Execute any and all validation rules present on this type and all nested field types.
     *
     * @return array
     */
    public function _getValidationErrors(): array;

    /**
     * @return string
     */
    public function __toString(): string;
}
<?php return ob_get_clean();