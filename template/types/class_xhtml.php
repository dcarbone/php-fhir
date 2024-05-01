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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

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
class <?php echo $type->getClassName(); ?> implements <?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE ?>, \JsonSerializable
{
    use <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>,
        <?php echo PHPFHIR_TRAIT_XMLNS; ?>;

    private const _PARENT_NODES = ['html', 'head', 'body'];
    private const _SIBLING_NODES = ['meta'];

    /** @var null|\DOMElement */
    private null|\DOMElement $_node = null;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\DOMNode $node
     * @param null|<?php echo $config->getNamespace(true); ?>\<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config
     */
    public function __construct(null|string|\DOMNode $node = null, null|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config = null)
    {
        $this->setNode($node, $config);
    }

    /**
     * @return null|\DOMNode
     */
    public function getNode(): null|\DOMNode
    {
        return $this->_node;
    }

    /**
     * @param null|string|\DOMNode $node
     * @param null|<?php echo $config->getNamespace(true); ?>\<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config
     * @return static
     */
    public function setNode(null|string|\DOMNode $node, null|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config = null): self
    {
        if (null === $node) {
            $this->_trackValueSet($this->_node, null);
            $this->_node = null;
            return $this;
        }
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        $dom = $config->newDOMDocument();
        if (is_string($node)) {
            // https://stackoverflow.com/a/8218649/11101981
            if (PHP_VERSION_ID >= 80200) {
                $dom->loadHTML(mb_encode_numericentity($node, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
            } else {
                $dom->loadHTML(mb_convert_encoding($node, 'HTML-ENTITIES', 'UTF-8'));
            }
        } else if ($node instanceof \DOMDocument) {
            $dom->appendChild($dom->importNode($node->documentElement, true));
        } else {
            $dom->appendChild($dom->importNode($node, true));
        }
        $newNode = $dom->documentElement;
        while (null !== $newNode) {
            if (in_array(strtolower($newNode->nodeName), self::_PARENT_NODES, true)) {
                $newNode = $newNode->firstChild;
            } else {
                break;
            }
        }
        if ('' !== ($ens = (string)$newNode?->namespaceURI)) {
            $this->_setFHIRXMLNamespace($ens);
        }
        $this->_trackValueSet($this->_node, $newNode);
        $this->_node = $newNode;
        return $this;
    }

<?php
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'unserialize' . DIRECTORY_SEPARATOR . 'header.php',
    [
        'config' => $config,
        'type' => $type,
        'typeKind' => $type->getKind(),
        'parentType' => null,
        'typeClassName' => $type->getClassName()
    ]
);
?>
        $type->setNode($element);
        return $type;
    }

    /**
     * @param null|\DOMElement $element
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return \DOMElement
     */
    public function xmlSerialize(\DOMElement $element = null, null|int|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG ?> $config = null): \DOMElement
    {
        if (is_int($config)) {
            $libxmlOpts = $config;
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        } else if (null === $config) {
            $libxmlOpts = <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_LIBXML_OPTS;
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        } else {
            $libxmlOpts = $config->getLibxmlOpts();
        }
        if (null === $element) {
            $dom = $config->newDOMDocument();
            $dom->loadXML($this->_getFHIRXMLElementDefinition('<?php echo $xmlName; ?>'), $libxmlOpts);
            $element = $dom->documentElement;
        } else if ('' !== ($ns = $this->_getFHIRXMLNamespace())) {
            $element->setAttribute('xmlns', $ns);
        }
        $node = $this->getNode();
        if (null === $node) {
            return $element;
        }
        for ($i = 0; $i < $node->attributes->length; $i++) {
            $attr = $node->attributes->item($i);
            $element->setAttribute($attr->nodeName, $attr->nodeValue);
        }
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $element->appendChild($element->ownerDocument->importNode($node->childNodes->item($i), true));
        }
        return $element;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        $node = $this->getNode();
        return $node?->ownerDocument->saveXML($node);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->jsonSerialize() ?? '';
    }
}<?php return ob_get_clean();