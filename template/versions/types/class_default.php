<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

// define some common things
$typeKind = $type->getKind();

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);

// -- property field name constants
if ($type->hasLocalProperties()) :
    echo "\n";
    foreach ($type->getProperties()->getGenerator() as $property) :
        if ($property->getMemberOf()->hasPrimitiveParent()) {
            continue;
        }

        $propertyType = $property->getValueFHIRType(); ?>
    public const <?php echo $property->getFieldConstantName(); ?> = '<?php echo $property->getName(); ?>';
<?php   if (null !== $propertyType &&
    ($propertyType->getKind() === TypeKindEnum::PRIMITIVE_CONTAINER || $propertyType->isValueContainer())) :
    ?>    public const <?php echo $property->getFieldConstantName(); ?>_EXT = '<?php echo $property->getExtName(); ?>';
<?php   endif;
    endforeach;
// -- end property field name constants

    echo "\n";

// -- directly implemented properties
    foreach ($type->getProperties()->getGenerator() as $property) : ?>
    /**
<?php echo DocumentationUtils::compilePropertyDocumentation($property, 5, true); ?>
     * @var <?php echo TypeHintUtils::propertyGetterDocHint($version, $property, true); ?>

     */
    protected <?php echo TypeHintUtils::propertyDeclarationHint($version, $property, true); ?> $<?php echo $property->getName(); ?> = <?php echo $property->isCollection() ? '[]' : 'null'; ?>;
<?php
// -- end directly implemented properties
    endforeach;
endif; ?>

    /** Default validation map for fields in type <?php echo $type->getFHIRName(); ?> */
    private const _DEFAULT_VALIDATION_RULES = [<?php if (!$type->hasPropertiesWithValidations()): ?>];
<?php else:
// -- property validation rules

    foreach ($type->getAllPropertiesIndexedIterator() as $property) :
        $validationMap = $property->buildValidationMap($type);
        if ([] !== $validationMap) : ?>

        self::<?php echo $property->getFieldConstantName(); ?> => [
<?php       foreach($validationMap as $k => $v) : ?>
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::<?php echo $k; ?> => <?php echo pretty_var_export($v, 3,false); ?>,
<?php       endforeach; ?>
        ],<?php
        endif;
endforeach; ?>

    ];
<?php
// -- end property validation rules
endif;

// -- end field properties
?>
<?php if ($type->hasLocalProperties()) : ?>

    /** @var array */
    private array $_xmlLocations = [];
<?php endif; ?>
<?php echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'constructor.php',
    [
        'version' => $version,
        'type' => $type,
    ]
); ?>

    /**
     * @return string
     */
    public function _getFHIRTypeName(): string
    {
        return self::FHIR_TYPE_NAME;
    }
<?php 

if ($type->isContainedType()) : ?>

    /**
     * @return string
     */
    public function _getResourceType(): string
    {
        return static::FHIR_TYPE_NAME;
    }
<?php
endif;

if ($type->hasLocalProperties()) :
    echo "\n";

    // --- property methods

    if ($type->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) :
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_PROPERTIES_DIR . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . 'primitive.php',
            [
                'version' => $version,
                'type' => $type
            ]
        );
    else :
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_PROPERTIES_DIR . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . 'default.php',
            [
                'version' => $version,
                'type' => $type,
            ]
        );
    endif;

    // --- end property methods
endif;

echo "\n";

echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);

if ($type->hasLocalProperties()) :
    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml.php',
        [
            'version' => $version,
            'type'     => $type,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'json.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );
endif;

if (!$type->hasPrimitiveParent()) : ?>

    /**
     * @return string
     */
    public function __toString(): string
    {
<?php if ($typeKind === TypeKindEnum::PRIMITIVE) : ?>
        return $this->getFormattedValue();
<?php elseif ($typeKind->isOneOf(TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE_CONTAINER)) : ?>
        return (string)$this->getValue();
<?php else : ?>
        return self::FHIR_TYPE_NAME;
<?php endif; ?>
    }
<?php endif; ?>}<?php return ob_get_clean();
