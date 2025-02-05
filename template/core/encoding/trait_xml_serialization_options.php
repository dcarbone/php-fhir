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
$imports->addCoreFileImports($valueXMLLocationEnum);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

trait <?php echo $coreFile; ?>

{
    /**
     * Returns the map of configured field value XML serialization locations for this type, keyed by field name.
     *
     * @return <?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?>[]
     */
    public function _getXMLFieldValueLocationMap(): array
    {
        return $this->_valueXMLLocations;
    }

    /**
     * Set the location a particular field's value must be placed when serializing this type to XML.  Each type has
     * a limited number of fields that may be serialized to XML
     *
     * @param string $field Name of field on this type.
     * @throws \DomainException
     */
    public function _setXMLFieldValueLocation(string $field, <?php echo $valueXMLLocationEnum; ?> $valueXMLLocation): void
    {
        if (!isset($this->_valueXMLLocations[$field])) {
            throw new \DomainException(sprintf(
                'Field "%s" on Type "%s" does not contain a value that is serializable as an attribute',
                $field,
                ltrim(substr(__CLASS__, (int)strrpos(__CLASS__, '\\')), '\\'),
            ));
        }
        $this->_valueXMLLocations[$field] = $valueXMLLocation;
    }

    /**
     * Returns the value serialization target for the given field's value on this type.
     *
     * @return <?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?>

     * @throws \DomainException
     */
    public function _getXMLFieldValueLocation(string $field): <?php echo $valueXMLLocationEnum; ?>

    {
        if (!isset($this->_valueXMLLocations[$field])) {
            throw new \DomainException(sprintf(
                'Field "%s" on Type "%s" does not contain a value that is serializable as an attribute',
                $field,
                ltrim(substr(__CLASS__, (int)strrpos(__CLASS__, '\\')), '\\'),
            ));
        }
        return $this->_valueXMLLocations[$field];
    }
}
<?php return ob_get_clean();
