<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassName = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$fhirName = $type->getFHIRName();
$sortedProperties = $type->getProperties()->getSortedIterator();

if (null === $parentType) {
    $primitiveType = $type->getPrimitiveType();
} else {
    $primitiveType = $parentType->getPrimitiveType();
}
if (null === $primitiveType) {
    var_dump($type->getFHIRName()); exit;
}
$phpValueType = $primitiveType->getPHPValueType();


if ($typeKind->isPrimitive()) :
    var_dump($type->getPattern());exit;
endif;

// begin output buffer
ob_start();

// first, build file header
echo require_with(
        PHPFHIR_TEMPLATE_FILE_DIR . '/header_type.php',
        [
                'fqns' => $fqns,
                'skipImports' => false,
                'type' => $type,
                'types' => $types,
                'config' => $config,
                'sortedProperties' => $sortedProperties,
        ]
);

// next, build class header ?>
/**
 * Class <?php echo $typeClassName; ?>

 * @package <?php echo $fqns; ?>

 */
<?php echo require_with(PHPFHIR_TEMPLATE_TYPES_DIR . '/definition.php', ['type' => $type, 'parentType' => $parentType]); ?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;

    const FIELD_VALUE = 'value';

    /** @var string */
    protected $_xmlns = '';

    /** @var null|<?php echo $phpValueType; ?> */
    protected $value = null;

<?php


    /**
     * @return null|<?php echo $primitiveType->getPHPValueType(); ?>

     */
    public function getValue()
    {
        return $this->value;
    }


echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . '/common.php',
    [
        'type' => $type,
        'parentType' => $parentType,
    ]
);

echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml.php',
    [
            'config' => $config,
            'type'     => $type,
            'typeKind' => $typeKind,
            'sortedProperties' => $sortedProperties,
            'parentType' => $parentType,
            'typeClassName' => $typeClassName,
    ]
) ?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json.php',
        [
                'type' => $type,
                'typeKind' => $typeKind,
                'primitiveType' => $primitiveType,
                'sortedProperties' => $sortedProperties,
                'parentType' => $parentType,
        ]
);

if ($type->isEnumerated()) : ?>

    /**
     * Returns the list of allowed values for this type
     * @return string[]
     */
    public function _getAllowedValueList()
    {
        return self::$_valueList;
    }

    /**
     * @return bool
     */
    public function _isValid()
    {
        $v = $this->getValue();
        return null === $v || in_array((string)$v, self::$_valueList, true);
    }
<?php endif; ?>

    /**
     * @return string
     */
    public function __toString()
    {
<?php if ($primitiveType->is(PrimitiveTypeEnum::BOOLEAN)) : ?>
        return $this->getValue() ? 'true' : 'false';
<?php else : ?>
        return (string)$this->getValue();
<?php endif; ?>
    }
}<?php return ob_get_clean();