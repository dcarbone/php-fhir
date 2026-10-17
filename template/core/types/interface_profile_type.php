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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);

$imports->addCoreFileImport(
    $typeInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Implemented by every class generated from a profile, such as a US Core StructureDefinition.
 */
interface <?php echo $coreFile; ?> extends <?php echo $typeInterface; ?>

{
    /**
     * Returns the canonical url of the profile, without version.
     *
     * @return string
     */
    public static function _getProfileUrl(): string;

    /**
     * Returns the business version of the profile, if it declares one.
     *
     * @return null|string
     */
    public static function _getProfileVersion(): null|string;

    /**
     * Executes only the validation rules added or tightened by the profile, and by the profiles it derives from,
     * against this type.  Constraints the base type already enforces, and errors in the types held by this type's
     * fields, are not reported.
     *
     * The returned map is keyed by the field or path and valued by a map of rule name => error.  An empty array means
     * the type conforms to the profile's rules.
     *
     * @return array
     */
    public function _getProfileValidationErrors(): array;
}
<?php return ob_get_clean();
