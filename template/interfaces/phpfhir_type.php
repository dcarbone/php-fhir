<?php declare(strict_types=1);

/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    public function __construct(?array $data = null);

    /**
     * Returns the FHIR name represented by this Type
     * @return string
     */
    public function _getFHIRTypeName(): string;

    /**
     * Returns the xml namespace to use for this type when serializing to XML, if applicable.
     * @return string
     */
    public function _getFHIRXMLNamespace(): string;

    /**
     * Set the XML Namespace to be output when serializing this type to XML
     * @param string $xmlNamespace
     * @return static
     */
    public function _setFHIRXMLNamespace(string $xmlNamespace): object;

    /**
     * Returns the base xml element definition for this type
     * @return string
     */
    public function _getFHIRXMLElementDefinition(): string;

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
     * Must return true if any field on this type is set to a non-empty value
     * @return bool
     */
    public function _isValued(): bool;

    /**
     * @param \DOMElement|string|null $element
     * @param null|static $type
     * @param null|int $libxmlOpts
     * @return null|static
     */
    public static function xmlUnserialize($element = null, <?php echo PHPFHIR_INTERFACE_TYPE; ?> $type = null, ?int $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>): ?object;

    /**
     * @param \DOMElement|null $element
     * @param null|int $libxmlOpts
     * @return string|\DOMElement
     */
    public function xmlSerialize(?\DOMElement $element = null, ?int $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>);

    /**
     * @return string
     */
    public function __toString(): string;
}
<?php return ob_get_clean();