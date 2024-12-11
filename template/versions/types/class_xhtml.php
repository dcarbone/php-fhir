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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();
$fqns = $type->getFullyQualifiedNamespace(true);
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$namespace = trim($fqns, PHPFHIR_NAMESPACE_TRIM_CUTSET);
$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'header_type.php',
    [
        'version' => $version,
        'fqns' => $fqns,
        'skipImports' => false,
        'type' => $type,
        'types' => $types,
    ]
);

// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $type->getClassName(); ?>

 * @package <?php echo $fqns; ?>

 */
class <?php echo $type->getClassName(); ?> implements <?php echo PHPFHIR_INTERFACE_TYPE ?>

{
    use <?php echo PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; ?>,
        <?php echo PHPFHIR_TRAIT_SOURCE_XMLNS; ?>;

    /** @var <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION_CONFIG); ?> */
    private <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?> $_config;

    /** @var null|string */
    private null|string $_xhtml = null;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\DOMNode|\SimpleXMLElement $xhtml
     */
    public function __construct(null|string|\DOMNode|\SimpleXmlElement $xhtml = null, <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?> $config = null)
    {
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?>();
        } else if (!($config instanceof <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?>)) {
            throw new \InvalidArgumentException(sprintf(
                '%s::__construct - $config must be instance of \\%s, \\%s seen',
                ltrim(substr(__CLASS__, (int)strrpos(__CLASS__, '\\')), '\\'),
                <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?>::class,
                is_object($config) ? get_class($config) : gettype($config)
            ));
        }
        $this->_config = $config;
        $this->setXhtml($xhtml);
    }

    /**
     * @return string
     */
    public function _getFhirTypeName(): string
    {
        return 'Xhtml';
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
    public function getXhtml(): null|string
    {
        return $this->_xhtml;
    }

    /**
     * @param null|string|\DOMNode|\SimpleXmlElement $xhtml
     * @return static
     */
    public function setXhtml(null|string|\DOMNode|\SimpleXMLElement $xhtml): self
    {
        if (null === $xhtml) {
            $this->_xhtml = null;
            return $this;
        }
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
     * @return null|\SimpleXMLElement
     * @throws \Exception
     */
    public function getSimpleXMLElement(): null|\SimpleXMLElement
    {
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        return new \SimpleXMLElement($xhtml, $this->_config->getSerializeConfig()->getLibxmlOpts());
    }

    /**
     * @return null|\DOMDocument
     */
    public function getDOMDocument(): null|\DOMDocument
    {
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?>();
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xhtml, $this->_config->getSerializeConfig()->getLibxmlOpts());
        return $dom;
    }

    /**
     * Returns open \XMLReader instance with content read
     *
     * @return null|\XMLReader
     */
    public function getXMLReader(): null|\XMLReader
    {
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        $xr = \XMLReader::XML($xhtml, 'UTF-8', $this->_config->getSerializeConfig()->getLibxmlOpts());
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
        'typeKind' => $type->getKind(),
        'parentType' => null,
        'typeClassName' => $type->getClassName()
    ]
);
?>
        $type->setXhtml($element);
        return $type;
    }

    /**
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?> $xw
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?>

     */
    public function xmlSerialize(null|<?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?> $xw = null): <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>

    {
        if (null === $xw) {
            $xw = new <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>();
        }
        if (!$xw->isOpen()) {
            $xw->openMemory();
        }
        if (!$xw->isDocStarted()) {
            $docStarted = true;
            $xw->startDocument();
        }
        if (!$xw->isRootOpen()) {
            $rootOpened = true;
            $xw->openRootNode($this->_config->getSerializeConfig(), 'Xhtml', $this->_getSourceXmlns());
        }
        $xr = $this->getXMLReader($this->_config);
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
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        return (string)$xhtml;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getXhtml();
    }
}
<?php return ob_get_clean();