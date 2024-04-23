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

    /** @var null|\DOMNode */
    private null|\DOMNode $_node = null;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\DOMNode $node
     */
    public function __construct(null|string|\DOMNode $node = null)
    {
        $this->setNode($node);
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
     * @return static
     */
    public function setNode(null|string|\DOMNode $node): self
    {
        if (null === $node) {
            $newNode = null;
        } else if (is_string($node)) {
            $dom = new \DOMDocument();
            $dom->loadHTML($node);
            $newNode = $dom->documentElement;
        } else if ($node instanceof \DOMDocument) {
            $dom = new \DOMDocument();
            $dom->appendChild($dom->importNode($node->documentElement, true));
            $newNode = $dom->documentElement;
        } else {
            $dom = new \DOMDocument();
            $dom->appendChild($dom->importNode($node, true));
            $newNode = $dom->documentElement;
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
     * @param \DOMElement|null $element
     * @param null|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config
     * @return \DOMElement
     */
    public function xmlSerialize(\DOMElement $element = null, null|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): \DOMElement
    {
        if (null === $element) {
            $dom = new \DOMDocument();
            $dom->loadXML($this->_getFHIRXMLElementDefinition('<?php echo $xmlName; ?>'), $config?->getLibxmlOpts() ?? 0);
            $element = $dom->documentElement;
        }
        $node = $this->getNode();
        if (null === $node) {
            return $element;
        }
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $element->appendChild($element->ownerDocument->importNode($node->childNodes->item($i)));
        }
        return $element;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        $node = $this->getNode();
        if (null === $node) {
            return null;
        }
        return $node->ownerDocument->saveXML($node);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->jsonSerialize() ?? '';
    }
}<?php return ob_get_clean();