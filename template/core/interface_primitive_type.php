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

$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

$imports = $coreFile->getImports();
$imports->addCoreFileImportsByName(
    PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo PHPFHIR_INTERFACE_PRIMITIVE_TYPE; ?>

{
    /**
     * Returns the FHIR name represented by this Type
     *
     * @return string
     */
    public function _getFHIRTypeName(): string;

    /**
     * Must return an associative array in structure ["field" => ["rule" => {constraint}]] to be used during validation
     *
     * @return array
     */
    public function _getValidationRules(): array;

    /**
     * Must return associative array where, if there are validation errors, the keys are the names of fields within the
     * type that failed validation.  The value must be a string message describing the manner of error
     *
     * @return array
     */
    public function _getValidationErrors(): array;

    /**
     * Must return the appropriate "formatted" stringified version of this primitive type's value
     *
     * @return string
     */
    public function _getFormattedValue(): string;

    /**
     * Set the XML location of this element's value when serializing
     *
     * @param <?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?> $valueXMLLocation
     */
    public function _setValueXMLLocation(<?php echo $valueXMLLocationEnum->getEntityName(); ?> $valueXMLLocation): void;

    /**
     * @return null|<?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?>

     */
    public function _getValueXMLLocation(): null|<?php echo $valueXMLLocationEnum->getEntityName(); ?>;

    /**
     * @return string
     */
    public function __toString(): string;
}
<?php return ob_get_clean();