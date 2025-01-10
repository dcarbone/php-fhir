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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();

$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
); ?>

    /** @var string */
    private string $_xhtml;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\DOMNode|\SimpleXMLElement $xhtml
     */
    public function __construct(null|string|\DOMNode|\SimpleXmlElement $xhtml = null)
    {
        $this->setXHTML($xhtml);
    }

    /**
     * @return string
     */
    public function _getFHIRTypeName(): string
    {
        return 'XHTML';
    }

    /**
     * @return array
     */
    public function _getValidationRules(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function _getValidationErrors(): array
    {
        return [];
    }

    /**
     * @return null|string
     */
    public function getXHTML(): null|string
    {
        return $this->_xhtml ?? null;
    }

    /**
     * Set the full XHTML content of this element.
     *
     * @param null|string|\DOMNode|\SimpleXmlElement $xhtml
     * @return static
     */
    public function setXHTML(null|string|\DOMNode|\SimpleXMLElement $xhtml): self
    {
        if ($xhtml instanceof \DOMDocument) {
            $xhtml = $xhtml->saveXML($xhtml->documentElement);
        } else if ($xhtml instanceof \DOMNode) {
            $xhtml = $xhtml->ownerDocument->saveXML($xhtml);
        } else if ($xhtml instanceof \SimpleXMLElement) {
            $xhtml = $xhtml->asXML();
        }
        $this->_xhtml = $xhtml;
        return $this;
    }

    /**
     * @param int $libxmlOpts libxml options mask
     * @return null|\SimpleXMLElement
     * @throws \Exception
     */
    public function getSimpleXMLElement(int $libxmlOpts): null|\SimpleXMLElement
    {
        if (!isset($this->_xhtml)) {
            return null;
        }
        return new \SimpleXMLElement($this->_xhtml, $libxmlOpts);
    }

    /**
     * @param int $libxmlOpts libxml options mask
     * @return null|\DOMDocument
     */
    public function getDOMDocument(int $libxmlOpts): null|\DOMDocument
    {
        if (!isset($this->_xhtml)) {
            return null;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($this->_xhtml, $libxmlOpts);
        return $dom;
    }

    /**
     * @param int $libxmlOpts libxml options mask
     * @return null|\XMLReader
     */
    public function getXMLReader(int $libxmlOpts): null|\XMLReader
    {
        if (!isset($this->_xhtml)) {
            return null;
        }
        $xr = \XMLReader::XML($this->_xhtml, 'UTF-8', $libxmlOpts);
        $xr->read();
        return $xr;
    }

<?php
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'unserialize' . DIRECTORY_SEPARATOR . 'header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);
?>
        $type->setXHTML($element);
        return $type;
    }

    /**
     * @param null|<?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param null|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?>

     */
    public function xmlSerialize(null|<?php echo PHPFHIR_ENCODING_CLASSNAME_XML_WRITER; ?> $xw = null, null|<?php echo PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG; ?> $config = null): <?php echo PHPFHIR_ENCODING_CLASSNAME_XML_WRITER; ?>

    {
        if (null === $xw) {
            $xw = new <?php echo PHPFHIR_ENCODING_CLASSNAME_XML_WRITER; ?>();
        }
        if (!$xw->isOpen()) {
            $xw->openMemory();
        }
        if (!$xw->isDocStarted()) {
            $docStarted = true;
            $xw->startDocument();
        }
        if (null === $config) {
            $config = (new <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION; ?>())->getConfig()->getSerializeConfig();
        }
        if (!$xw->isRootOpen()) {
            $rootOpened = true;
            $xw->openRootNode($config, 'XHTML', $this->_getSourceXMLNS());
        }
        $xr = $this->getXMLReader($config->getXHTMLLibxmlOpts());
        if (null === $xr) {
            return $xw;
        }
        while ($xr->moveToNextAttribute()) {
            $xw->writeAttribute($xr->name, $xr->value);
        }
        $xw->writeRaw($xr->readInnerXml());
        if (isset($rootOpened) && $rootOpened) {
            $xw->endElement();
        }
        if (isset($docStarted) && $docStarted) {
            $xw->endDocument();
        }
        return $xw;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        if (!isset($this->_xhtml)) {
            return null;
        }
        return (string)$this->_xhtml;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getXHTML();
    }
}
<?php return ob_get_clean();