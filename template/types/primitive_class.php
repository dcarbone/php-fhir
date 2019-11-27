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
$typeFile = null;
switch($primitiveType->getValue()) {
    case PrimitiveTypeEnum::STRING:
        $typeFile = __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::BOOLEAN:
        $typeFile = __DIR__ . '/primitive/bool_type.php';
        break;

    // int types
    case PrimitiveTypeEnum::INTEGER:
    case PrimitiveTypeEnum::POSITIVE_INTEGER:
    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
        $typeFile = __DIR__ . '/primitive/integer_type.php';
        break;

    // treat uint64's as strings for the moment.
    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        $typeFile =  __DIR__ . '/primitive/unsigned_integer_type.php';
        break;

    case PrimitiveTypeEnum::DECIMAL:
        $typeFile = __DIR__ . '/primitive/decimal_type.php';
        break;

    // date types
    case PrimitiveTypeEnum::DATE:
        $typeFile = __DIR__ . '/primitive/date_type.php';
        break;
    case PrimitiveTypeEnum::DATETIME:
        $typeFile = __DIR__ . '/primitive/datetime_type.php';
        break;
    case PrimitiveTypeEnum::TIME:
        $typeFile = __DIR__ . '/primitive/time_type.php';
        break;
    case PrimitiveTypeEnum::INSTANT:
        $typeFile = __DIR__ . '/primitive/instant_type.php';
        break;

    case PrimitiveTypeEnum::URI:
        $typeFile = __DIR__.'/primitive/uri_type.php';
        break;

    case PrimitiveTypeEnum::CODE:
        $typeFile = __DIR__ . '/primitive/code_type.php';
        break;

    case PrimitiveTypeEnum::OID:
        $typeFile = __DIR__.'/primitive/oid_type.php';
        break;

    case PrimitiveTypeEnum::ID:
        $typeFile = __DIR__.'/primitive/id_type.php';
        break;

    // TODO: create specific URL type?
    case PrimitiveTypeEnum::CANONICAL:
    case PrimitiveTypeEnum::URL:
        $typeFile =  __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::UUID:
        // TODO: implement uuid lib?
        $typeFile = __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::BASE_64_BINARY:
        // TODO: add content decoding?
        $typeFile =  __DIR__ . '/primitive/base64_binary_type.php';
        break;

    case PrimitiveTypeEnum::MARKDOWN:
        // TODO: markdown lib, maybe?
        $typeFile =  __DIR__ . '/primitive/markdown_type.php';
        break;

    case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
        $typeFile =  __DIR__ . '/primitive/string_type.php';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}

echo require_with(
        $typeFile,
        [
                'fhirName' => $fhirName,
                'type' => $type,
                'primitiveType' => $primitiveType,
                'typeClassName' => $typeClassName
        ]
);
?>

    /**
     * @return null|<?php echo $primitiveType->getPHPValueType(); ?>

     */
    public function getValue()
    {
        return $this->value;
    }

<?php

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