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
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();
$imports = $type->getimports();

$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$typeValidationTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_TRAIT_TYPE_VALIDATIONS);

$imports->addCoreFileImports($primitiveTypeInterface, $typeValidationTrait);

$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $type->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $type->getClassName(); ?> implements <?php echo $primitiveTypeInterface; ?>

{
    use <?php echo $typeValidationTrait; ?>;

    /** @var string */
    protected string $value;

    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param null|string|\DOMNode|\SimpleXMLElement $value
     */
    public function __construct(null|string|\DOMNode|\SimpleXmlElement $value = null)
    {
        $this->setValue($value);
    }

    /**
     * @return string
     */
    public function _getFHIRTypeName(): string
    {
        return 'XHTML';
    }

    /**
     * @return null|string
     */
    public function getValue(): null|string
    {
        return $this->value ?? null;
    }

    /**
     * Set the full XHTML content of this element.
     *
     * @param null|string|\DOMNode|\SimpleXmlElement $value
     * @return static
     */
    public function setValue(null|string|\DOMNode|\SimpleXMLElement $value): self
    {
        if (null === $value) {
            unset($this->value);
            return $this;
        }
        if ($value instanceof \DOMDocument) {
            $value = $value->saveXML($value->documentElement);
        } else if ($value instanceof \DOMNode) {
            $value = $value->ownerDocument->saveXML($value);
        } else if ($value instanceof \SimpleXMLElement) {
            $value = $value->asXML();
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @param int $libxmlOpts libxml options mask
     * @return null|\SimpleXMLElement
     * @throws \Exception
     */
    public function getSimpleXMLElement(int $libxmlOpts): null|\SimpleXMLElement
    {
        if (!isset($this->value)) {
            return null;
        }
        return new \SimpleXMLElement($this->value, $libxmlOpts);
    }

    /**
     * @param int $libxmlOpts libxml options mask
     * @return null|\DOMDocument
     */
    public function getDOMDocument(int $libxmlOpts): null|\DOMDocument
    {
        if (!isset($this->value)) {
            return null;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($this->value, $libxmlOpts);
        return $dom;
    }

    /**
     * @param int $libxmlOpts libxml options mask
     * @return null|\XMLReader
     */
    public function getXMLReader(int $libxmlOpts): null|\XMLReader
    {
        if (!isset($this->value)) {
            return null;
        }
        $xr = \XMLReader::XML($this->value, 'UTF-8', $libxmlOpts);
        $xr->read();
        return $xr;
    }

    /**
     * @return null|string
     */
    public function jsonSerialize(): null|string
    {
        if (!isset($this->value)) {
            return null;
        }
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getValue();
    }
}
<?php return ob_get_clean();
