<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE; ?>

{
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
    public function _setFHIRXMLNamespace(string $xmlNamespace): self;

    /**
     * Returns the base xml element definition for this type
     *
     * @param string $elementName Name of the root element
     * @return string
     */
    public function _getFHIRXMLElementDefinition(string $elementName): string;

    /**
     * @param null|string|\DOMElement $element
     * @param null|static $type
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return null|static
     */
    public static function xmlUnserialize(null|string|\DOMElement $element, <?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE; ?> $type = null, null|int|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG ?> $config = null): null|self;

    /**
     * @param null|\DOMElement $element
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return \DOMElement
     */
    public function xmlSerialize(null|\DOMElement $element = null, null|int|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG ?> $config = null): \DOMElement;
}
<?php return ob_get_clean();