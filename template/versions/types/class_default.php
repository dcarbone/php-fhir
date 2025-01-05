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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

// define some common things
$typeClassName = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$localProperties = $type->getLocalProperties()->getLocalPropertiesIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$interfaces = $type->getDirectlyImplementedInterfaces();
$traits = $type->getDirectlyUsedTraits();

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'header.php',
    [
        'version' => $version,
        'skipImports' => false,
        'type' => $type,
        'types' => $types,
    ]
);

?>
<?php if ('' !== $classDocumentation) : ?>/**

<?php echo $classDocumentation; ?>

 */
<?php endif;
// -- class definition
if ($type->isAbstract()) : ?>abstract <?php endif; ?>class <?php echo $type->getClassName(); ?><?php if (null !== $parentType) : ?> extends <?php echo $parentType->getClassName(); endif; ?>
<?php if ([] !== $interfaces) : ?> implements <?php echo implode(', ', array_keys($interfaces)); endif; ?>

{<?php if ([] !== $traits) : ?>

    use <?php echo implode(",\n        ", array_keys($traits)); ?>;
<?php endif;
?>

    // name of FHIR type this class describes
    public const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;
<?php

// -- property field name constants
if (0 !== count($localProperties)) :
    echo "\n";
    foreach ($localProperties as $property) :
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
    foreach ($localProperties as $property) : ?>
    /**
<?php echo DocumentationUtils::compilePropertyDocumentation($property, 5, true);; ?>
     * @var <?php echo TypeHintUtils::propertyGetterTypeDoc($version, $property, true); ?>

     */
    protected <?php echo TypeHintUtils::propertyTypeHint($version, $property, true); ?> $<?php echo $property->getName(); ?> = <?php echo $property->isCollection() ? '[]' : 'null'; ?>;
<?php
// -- end directly implemented properties
    endforeach; ?>


    /** Default validation map for fields in type <?php echo $type->getFHIRName(); ?> */
    private const _DEFAULT_VALIDATION_RULES = [<?php if (!$type->hasLocalPropertiesWithValidations()): ?>];
<?php else:
// -- local property validation rules

    foreach ($localProperties as $property) :
        $validationMap = $property->buildValidationMap();
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
endif;
// -- end local property validation rules

endif;

// -- end field properties
?>


    /** @var array */
    private array $_xmlLocations = [];


<?php echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'constructor.php',
    [
        'version' => $version,
        'type' => $type,
        'properties' => $localProperties,
        'parentType' => $parentType,
    ]
); ?>


<?php echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'common.php',
    [
        'version' => $version,
        'type' => $type,
        'parentType' => $type->getParentType(),
    ]
);

if ($type->isContainedType()) {
    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'contained_type.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );
}

if (0 < count($localProperties)) {
    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_PROPERTIES_DIR . DIRECTORY_SEPARATOR . 'methods.php',
        [
            'version' => $version,
            'type' => $type,
            'properties' => $localProperties,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml.php',
        [
            'version' => $version,
            'type'     => $type,
            'typeKind' => $typeKind,
            'parentType' => $parentType,
            'typeClassName' => $typeClassName,
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
}

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
