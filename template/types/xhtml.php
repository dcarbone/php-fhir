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
<?php echo require_with(PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'definition.php', ['config' => $config, 'type' => $type, 'parentType' => null]); ?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;

    /** @var null|\DOMNode */
    private ?\DOMNode $_data = null;
    /** @var null|string */
    private ?string $_elementName = null;
    /** @var string */
    private string $_xmlns = '';

    /** @var array */
    private static array $_validationRules = [];

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string|\SimpleXMLElement|\DOMNode $data
     */
    public function __construct($data = null)
    {
        $this->_setData($data);
    }

    /**
     * The name of the FHIR element this raw type represents
     *
     * @param string $elementName
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function _setElementName(string $elementName): <?php echo $type->getClassName(); ?>

    {
        $this->_elementName = $elementName;
        return $this;
    }

<?php
echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . DIRECTORY_SEPARATOR . 'common.php',
    [
        'config' => $config,
        'type' => $type,
        'parentType' => $type->getParentType(),
    ]
);
?>

    /**
     * @return null|\DOMNode
     */
    public function _getData(): ?\DOMNode
    {
        return $this->_data;
    }

    /**
     * @param null|string|\SimpleXMLElement|\DOMNode $data
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function _setData($data): <?php echo $type->getClassName(); ?>

    {
        if (null === $data) {
            $this->_data = null;
            return $this;
        }
        if (is_string($data)) {
            $dom = new \DOMDocument();
            $dom->loadHTML($data);
            $this->_data = $dom->documentElement;
            return $this;
        }
        if ($data instanceof \SimpleXMLElement) {
            $dom = new \DOMDocument();
            $dom->appendChild($dom->importNode(dom_import_simplexml($data), true));
            $this->_data = $dom->documentElement;
            return $this;
        }
        if ($data instanceof \DOMDocument) {
            $dom = new \DOMDocument();
            $dom->appendChild($dom->importNode($data->documentElement, true));
            $this->_data = $dom->documentElement;
            return $this;
        }
        if ($data instanceof \DOMNode) {
            $dom = new \DOMDocument();
            $dom->appendChild($dom->importNode($data, true));
            $this->_data = $dom->documentElement;
            return $this;
        }
        throw new \InvalidArgumentException(sprintf(
            '$data must be one of: null, valid XHTML string, or instance of \\SimpleXMLElement or \\DOMNode, saw "%s"',
            gettype($data)
        ));
    }


<?php echo require_with(
        PHPFHIR_TEMPLATE_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods.php',
    [
        'config' => $config,
        'type' => $type,
    ]
); ?>

<?php
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'unserialize' . DIRECTORY_SEPARATOR . 'header.php',
    [
        'config' => $config,
        'type' => $type,
        'typeKind' => $type->getKind(),
        'parentType' => null,
        'typeClassName' => $type->getClassName()
    ]
);
?>
        $type->_setData($element);
        return $type;
    }

     /**
     * @param \DOMElement|null $element
     * @param null|int $libxmlOpts
     * @return \DOMElement
     */
    public function xmlSerialize(\DOMElement $element = null, ?int $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>): \DOMElement
    {
        $data = $this->_getData();
        $xmlns = $this->_getFHIRXMLNamespace();
        if (null === $element) {
            $dom = new \DOMDocument();
            if (!empty($xmlns)) {
                $xmlns = " xmlns=\"{$xmlns}\"";
            }
            if (null === $data) {
                $dom->loadXML("<<?php echo $xmlName; ?>{$xmlns}></<?php echo $xmlName; ?>>", $libxmlOpts);
                return $dom->documentElement;
            }
            $dom->appendChild($dom->importNode($data, true));
            return $dom->documentElement;
        }
        if (null === $data) {
            return $element;
        }
        if (!empty($xmlns)) {
            $element->setAttribute('xmlns', $xmlns);
        }
        $element->appendChild($element->ownerDocument->importNode($data, true));
        return $element;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        $data = $this->_getData();
        if (null === $data) {
            return null;
        }
        return $data->ownerDocument->saveXML($data);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $data = $this->_getData();
        if (null === $data) {
            return '';
        }
        return $data->ownerDocument->saveXML($data);
    }
}<?php return ob_get_clean();