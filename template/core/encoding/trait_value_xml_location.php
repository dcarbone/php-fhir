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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

ob_start();

echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

trait <?php echo PHPFHIR_ENCODING_TRAIT_VALUE_XML_LOCATION; ?>

{
    private <?php echo $valueXMLLocationEnum->getEntityName(); ?> $_valueLoc;

    /**
     * Set the XML location of this element's value when serializing
     *
     * @param <?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?> $valueXMLLocation
     */
    public function _setValueXMLLocation(<?php echo $valueXMLLocationEnum->getEntityName(); ?> $valueXMLLocation): void
    {
        $this->_valueLoc = $valueXMLLocation;
    }

    /**
     * @return null|<?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?>

     */
    public function _getValueXMLLocation(): null|<?php echo $valueXMLLocationEnum->getEntityName(); ?>

    {
        return $this->_valueLoc ?? null;
    }
}
<?php return ob_get_clean();
