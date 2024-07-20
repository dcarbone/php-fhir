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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$fqns = $type->getFullyQualifiedNamespace(true);
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$namespace = trim($fqns, PHPFHIR_NAMESPACE_TRIM_CUTSET);
$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_FILE_DIR . DIRECTORY_SEPARATOR . 'header_type.php',
    [
        'config' => $config,
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
    use <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>,
        <?php echo PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; ?>,
        <?php echo PHPFHIR_TRAIT_SOURCE_XMLNS; ?>;

    /** @var null|string */
    private null|string $_xhtml = null;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\DOMNode|\SimpleXMLElement $xhtml
     */
    public function __construct(null|string|\DOMNode|\SimpleXmlElement $xhtml = null)
    {
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
            $this->_trackValueSet($this->_xhtml, null);
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
        $this->_trackValueSet($this->_xhtml, $xhtml);
        $this->_xhtml = $xhtml;
        return $this;
    }

    /**
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     * @return null|\SimpleXMLElement
     * @throws \Exception
     */
    public function getSimpleXMLElement(null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null): null|\SimpleXMLElement
    {
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        return new \SimpleXMLElement($xhtml, $config->getLibxmlOpts());
    }

    /**
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     * @return null|\DOMDocument
     */
    public function getDOMDocument(null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null): null|\DOMDocument
    {
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xhtml, $config->getLibxmlOpts());
        return $dom;
    }

    /**
     * Returns open \XMLReader instance with content read
     *
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     * @return null|\XMLReader
     */
    public function getXMLReader(null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null): null|\XMLReader
    {
        $xhtml = $this->getXhtml();
        if (null === $xhtml) {
            return null;
        }
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        $xr = \XMLReader::XML($xhtml, 'UTF-8', $config->getLibxmlOpts());
        $xr->read();
        return $xr;
    }

<?php
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'unserialize' . DIRECTORY_SEPARATOR . 'header.php',
    [
        'config' => $config,
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
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?>

     */
    public function xmlSerialize(null|<?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?> $xw = null, null|int|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>

    {
        if (is_int($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>([<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::LIBXML_OPTS->value => $config]);
        } else if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
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
            $xw->openRootNode($config, 'Xhtml', $this->_getSourceXmlns());
        }
        $xr = $this->getXMLReader($config);
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
}<?php return ob_get_clean();