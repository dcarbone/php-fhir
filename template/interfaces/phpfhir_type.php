<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_TYPE; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_TYPE; ?> extends \JsonSerializable {
    /**
     * @param array|null $data
     */
    public function __construct($data = null);

    /**
     * Returns the FHIR name represented by this Type
     * @return string
     */
    public function _getFHIRTypeName();

    /**
     * Returns the xml namespace to use for this type when serializing to XML, if applicable.
     * @return string
     */
    public function _getFHIRXMLNamespace();

    /**
     * Set the XML Namespace to be output when serializing this type to XML
     * @param string $xmlNamespace
     * @return static
     */
    public function _setFHIRXMLNamespace($xmlNamespace);

    /**
     * Returns the base xml element definition for this type
     * @return string
     */
    public function _getFHIRXMLElementDefinition();

    /**
     * Must return associative array where, if there are validation errors, the keys are the names of fields within the
     * type that failed validation.  The value must be a string message describing the manner of error
     * @return array
     */
    public function _validationErrors();

    /**
     * @param \SimpleXMLElement|string|null $sxe
     * @param null|static $type
     * @param null|int $libxmlOpts
     * @return null|static
     */
    public static function xmlUnserialize($sxe = null, <?php echo PHPFHIR_INTERFACE_TYPE; ?> $type = null, $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>);

    /**
     * @param null|\SimpleXMLElement $sxe
     * @param null|int $libxmlOpts
     * @return string|\SimpleXMLElement
     */
    public function xmlSerialize(\SimpleXMLElement $sxe = null, $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>);

    /**
     * @return string
     */
    public function __toString();
}
<?php return ob_get_clean();