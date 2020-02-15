<?php

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
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$fqns = $type->getFullyQualifiedNamespace(true);
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$namespace = trim($fqns, PHPFHIR_NAMESPACE_TRIM_CUTSET);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_FILE_DIR . '/header_type.php',
    [
        'fqns' => $fqns,
        'skipImports' => false,
        'type' => $type,
        'types' => $types,
        'config' => $config,
    ]
);

// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $type->getClassName(); ?>

 * @package <?php echo $fqns; ?>

 */
<?php echo require_with(PHPFHIR_TEMPLATE_TYPES_DIR . '/definition.php', ['type' => $type, 'parentType' => null]); ?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;
    const TO_STRING_FUNC = '__toString';

    /** @var string */
    private $_data = null;
    /** @var string */
    private $_elementName = null;
    /** @var string */
    private $_xmlns = '';

    /** @var array */
    private static $_validationRules = [];

    /**
     * <?php echo PHPFHIR_RAW_TYPE_NAME; ?> Constructor
     * @param null|string|integer|float|boolean|object $data
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
    public function _setElementName($elementName)
    {
        $this->_elementName = $elementName;
        return $this;
    }

<?php
echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . '/common.php',
    [
        'type' => $type,
        'parentType' => $type->getParentType(),
    ]
);
?>

    /**
     * @return null|string|integer|float|boolean|object
     */
    public function _getData()
    {
        return $this->_data;
    }

    /**
     * @param mixed $data
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function _setData($data)
    {
        if (null === $data) {
            $this->_data = null;
            return $this;
        }
        if (is_scalar($data) || (is_object($data) && method_exists($data, self::TO_STRING_FUNC))) {
            $this->_data = $data;
            return $this;
        }
        throw new \InvalidArgumentException(sprintf(
            '$data must be one of: null, string, integer, double, boolean, or object implementing "__toString", saw "%s"',
            gettype($data)
        ));
    }


<?php echo require_with(
        PHPFHIR_TEMPLATE_VALIDATION_DIR . '/methods.php',
    [
            'type' => $type,
    ]
); ?>

<?php
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/header.php',
    [
        'config' => $config,
        'type' => $type,
        'typeKind' => $type->getKind(),
        'parentType' => null,
        'typeClassName' => $type->getClassName()
    ]
);
?>
        $type->_setData((string)$sxe);
        return $type;
    }

     /**
     * @param null|\SimpleXMLElement $sxe
     * @param null|int $libxmlOpts
     * @return \SimpleXMLElement
     */
    public function xmlSerialize(\SimpleXMLElement $sxe = null, $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>)
    {
        if (null !== $sxe) {
            throw new \LogicException(sprintf(
                'Due to limitations with the PHP implementation of SimpleXML, the %s type cannot accept a pre-built SimpleXMLElement instance to %s',
                '<?php echo PHPFHIR_RAW_TYPE_NAME; ?>',
                __METHOD__
            ));
        }
        $xmlns = '';

        return new \SimpleXMLElement("<{$this->_getElementName()}>{$this->_getData()}</{$this->_getElementName()}>", $libxmlOpts, false);
    }

    /**
     * @return null|string|integer|float|boolean|object
     */
    public function jsonSerialize()
    {
        return $this->_getData();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strval($this->_getData());
    }

}<?php return ob_get_clean();