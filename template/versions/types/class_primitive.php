<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$imports = $type->getImports();
$coreFiles = $version->getConfig()->getCoreFiles();
$versionCoreFiles = $version->getVersionCoreFiles();

$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);

$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);

$imports->addCoreFileImports(
    $fhirVersion,
    $versionClass,
);

$typeKind = $type->getKind();
$typeClassName = $type->getClassName();
$primitiveType = $type->getPrimitiveType();
$valueProperty = $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);

$validationMap = $valueProperty ? $valueProperty->buildValidationMap($type) : [];

$stringSerializable = $primitiveType->isOneOf(
    PrimitiveTypeEnum::BOOLEAN,
    PrimitiveTypeEnum::DECIMAL,
    PrimitiveTypeEnum::INTEGER64,
    PrimitiveTypeEnum::INTEGER,
    PrimitiveTypeEnum::NEGATIVE_INTEGER,
    PrimitiveTypeEnum::POSITIVE_INTEGER,
    PrimitiveTypeEnum::UNSIGNED_INTEGER,
);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . '/header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
); ?>
    public const <?php echo $valueProperty->getFieldConstantName(); ?> = '<?php echo $valueProperty->getName(); ?>';

<?php if ($primitiveType === PrimitiveTypeEnum::BOOLEAN) : ?>
    public const TRUE = 'true';
    public const FALSE = 'false';

<?php endif; ?>
    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    private const _FHIR_VALIDATION_RULES = [<?php if ([] === $validationMap): ?>];
<?php else: ?>

        self::<?php echo $valueProperty->getFieldConstantName(); ?> => [
<?php
    foreach($validationMap as $k => $v) : ?>
            <?php echo $k; ?>::NAME => <?php echo pretty_var_export($v, 3); ?>,
<?php
    endforeach; ?>
        ],
    ];
<?php
// -- end property validation rules
endif;

// only define constructor if this primitive does not have a parent.
if (!$type->hasParent()) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
<?php
        $documentation = DocumentationUtils::compilePropertyDocumentation($valueProperty, 5, true); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *
     *<?php endif; ?> @var <?php echo TypeHintUtils::propertyGetterDocHint($version, $valueProperty, false); ?> <?php
        if ('' !== $documentation) : ?>

     <?php endif; ?>*/
    protected <?php echo TypeHintUtils::propertyDeclarationHint($version, $valueProperty, false); ?> $<?php echo $valueProperty->getName(); ?>;
<?php if ($stringSerializable) : ?>

    /** @var bool */
    private bool $_jsonAsString;
<?php endif; ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true); ?> $value
<?php if ($stringSerializable) : ?>
     * @param bool $jsonAsString If true forces this value to string during JSON serialization.
<?php endif; ?>
     */
    public function __construct(<?php echo TypeHintUtils::buildSetterParameterHint($version, $valueProperty, true); ?> $value = null<?php if ($stringSerializable) : ?>,
                                bool $jsonAsString = false<?php endif; ?>)
    {
        $this->setValue($value);
<?php if ($stringSerializable) : ?>
        $this->_jsonAsString = $jsonAsString;
<?php endif; ?>
    }
<?php endif; ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    /**
     * @return string
     */
    public function _getFHIRTypeName(): string
    {
        return self::FHIR_TYPE_NAME;
    }
<?php if (!$type->hasParent()) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function _getFHIRVersion(): <?php echo $fhirVersion; ?>

    {
        return <?php echo $versionClass; ?>::getFHIRVersion();
    }
<?php   if ($stringSerializable) : ?>
    /**
     * Specify whether this value must be represented as a string when serializing to JSON.
     *
     * @param bool $jsonAsString
     * @return self
     */
    public function _setJSONAsString(bool $jsonAsString): self
    {
        $this->_jsonAsString = $jsonAsString;
        return $this;
    }

    /**
     * @return bool
     */
    public function _getJSONAsString(): bool
    {
        return $this->_jsonAsString;
    }

<?php endif; ?>
    /**
     * @return <?php echo TypeHintUtils::primitivePHPValueTypeHint($version, $primitiveType, true); ?>

     */
    public function getValue(): <?php echo TypeHintUtils::primitivePHPValueTypeHint($version, $primitiveType, true); ?>

    {
        return $this->value ?? null;
    }

<?php
switch ($primitiveType) {
    // string types
    case PrimitiveTypeEnum::CANONICAL:
    case PrimitiveTypeEnum::CODE:
    case PrimitiveTypeEnum::STRING:
    case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
    case PrimitiveTypeEnum::ID:
    case PrimitiveTypeEnum::OID:
    case PrimitiveTypeEnum::URI:
    case PrimitiveTypeEnum::MARKDOWN: // TODO: markdown lib, maybe?
    case PrimitiveTypeEnum::UUID: // TODO: implement uuid lib?
    case PrimitiveTypeEnum::URL: // TODO: create specific URL type?
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/string_type.php';
        break;

    case PrimitiveTypeEnum::BOOLEAN:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/bool_type.php';
        break;

    // int types
    case PrimitiveTypeEnum::INTEGER:
    case PrimitiveTypeEnum::INTEGER64:
    case PrimitiveTypeEnum::POSITIVE_INTEGER:
    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/integer_type.php';
        break;

    // treat uint64's as strings for the moment.
    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/unsigned_integer_type.php';
        break;

    case PrimitiveTypeEnum::DECIMAL:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/decimal_type.php';
        break;

    // date types
    case PrimitiveTypeEnum::DATE:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/date_type.php';
        break;
    case PrimitiveTypeEnum::DATETIME:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/datetime_type.php';
        break;
    case PrimitiveTypeEnum::TIME:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/time_type.php';
        break;
    case PrimitiveTypeEnum::INSTANT:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/instant_type.php';
        break;

    case PrimitiveTypeEnum::BASE64_BINARY:
        // TODO: add content decoding?
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitives/base64_binary_type.php';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}

echo require_with(
    $typeFile,
    [
        'version' => $version,
        'type' => $type,
        'primitiveType' => $primitiveType
    ]
); ?>

    public function __toString(): string
    {
        return $this->_getValueAsString();
    }
<?php endif; ?>
}
<?php return ob_get_clean();
