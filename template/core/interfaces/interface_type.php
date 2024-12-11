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


/** @var \DCarbone\PHPFHIR\Config $config */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_TYPE; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_TYPE; ?> extends \JsonSerializable
{
    /**
     * Returns the FHIR name represented by this Type
     * @return string
     */
    public function _getFhirTypeName(): string;

    /**
     * Returns the root Xmlns value found in the source.  Null indicates no "xmlns" was found.  Only defined when
     * unserializing XML, and only used when serializing XML.
     *
     * @return null|string
     */
    public function _getSourceXmlns(): null|string;

    /**
     * Must return an associative array in structure ["field" => ["rule" => {constraint}]] to be used during validation
     * @return array
     */
    public function _getValidationRules(): array;

    /**
     * Must return associative array where, if there are validation errors, the keys are the names of fields within the
     * type that failed validation.  The value must be a string message describing the manner of error
     * @return array
     */
    public function _getValidationErrors(): array;

    /**
     * @param null|string|\SimpleXMLElement $element
     * @param null|static $type
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?> $config
     * @return null|static
     */
    public static function xmlUnserialize(null|string|\SimpleXMLElement $element, <?php echo PHPFHIR_INTERFACE_TYPE; ?> $type = null, null|<?php echo PHPFHIR_INTERFACE_VERSION_CONFIG ?> $config = null): null|self;

    /**
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?> $xw
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?>

     */
    public function xmlSerialize(null|<?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?> $xw = null): <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>;

    /**
     * @return string
     */
    public function __toString(): string;
}
<?php return ob_get_clean();