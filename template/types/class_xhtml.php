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
class <?php echo $type->getClassName(); ?> implements <?php echo PHPFHIR_INTERFACE_TYPE ?>

{
    use <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>,
        <?php echo PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; ?>,
        <?php echo PHPFHIR_TRAIT_XMLNS; ?>;

    /** @var null|\SimpleXMLElement */
    private null|\SimpleXMLElement $_node = null;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\DOMNode|\SimpleXmlElement $node
     * @param null|<?php echo $config->getNamespace(true); ?>\<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config
     */
    public function __construct(null|string|\DOMNode|\SimpleXmlElement $node = null, null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null)
    {
        $this->setNode($node, $config);
    }

    /**
     * @return string
     */
    public function _getFhirTypeName(): string
    {
        return 'Xhtml';
    }

    /**
     * @return null|\SimpleXMLElement
     */
    public function getNode(): null|\SimpleXMLElement
    {
        return $this->_node;
    }

    /**
     * @param string $elementName Name to use for the element
     * @return string
     * @throws \InvalidArgumentException
     */
    public function _getFhirXmlElementDefinition(string $elementName): string
    {
        if ('' === $elementName) {
            throw new \InvalidArgumentException(sprintf('%s::_getFhirXmlElementDefinition - $elementName is required', get_called_class()));
        }
        $node = $this->getNode();
        if (null === $node) {
            $xmlns = $this->_getFhirXmlNamespace();
            if ('' !==  $xmlns) {
                $xmlns = sprintf(' xmlns="%s"', $xmlns);
            }
            return sprintf('<%1$s%2$s></%1$s>', $elementName, $xmlns);
        }
        $xml = $node->asXML();
        return substr($xml, strpos($xml, "\n") + 1, -1);
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
     * Recursively copies nodes from one sxe to another
     *
     * @param \SimpleXMLElement $dest
     * @param \SimpleXMLElement $src
     */
    private static function _copy(\SimpleXMLElement $dest, \SimpleXMLElement $src): void
    {
        if (null === $src) {
            return;
        }
        foreach ($src->attributes() as $k => $v) {
            $dest->addAttribute($k, (string)$v);
        }
        foreach ($src->children() as $child) {
            $babe = $dest->addChild($child->getName(), (string)$child);
            self::_copy($babe, $child);
        }
    }

    /**
     * @param null|string|\DOMNode|\SimpleXmlElement $node
     * @param null|<?php echo $config->getNamespace(true); ?>\<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config
     * @return static
     */
    public function setNode(null|string|\DOMNode|\SimpleXMLElement $node, null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null): self
    {
        if (null === $node) {
            $this->_trackValueSet($this->_node, null);
            $this->_node = null;
            return $this;
        }
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        if (is_string($node)) {
            $node = new \SimpleXMLElement($node, $config->getLibxmlOpts());
        } else if ($node instanceof \DOMDocument) {
            $node = simplexml_import_dom($node);
        } else {
            $node = simplexml_import_dom($node->ownerDocument);
        }
        if ('' !== ($ens = (string)$node->attributes['xmlns'])) {
            $this->_setFhirXmlNamespace($ens);
        }
        $this->_trackValueSet($this->_node, $node);
        $this->_node = $node;
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
     * @param null|\SimpleXMLElement $element
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return \SimpleXMLElement
     */
    public function xmlSerialize(\SimpleXMLElement $element = null, null|int|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): \SimpleXMLElement
    {
        if (is_int($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>(['libxmlOpts' => $config]);
        } else if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        if (null === $element) {
            return new \SimpleXMLElement($this->_getFhirXmlElementDefinition('<?php echo $xmlName; ?>'), $config->getLibxmlOpts());
        }
        $node = $this->getNode();
        if (null === $node) {
            return $element;
        }
        self::_copy($element, $node);
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
        $xml = $node->asXML();
        return substr($xml, strpos($xml, "\n") + 1, -1);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->jsonSerialize();
    }
}<?php return ob_get_clean();