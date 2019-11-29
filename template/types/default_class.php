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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassname = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$fhirName = $type->getFHIRName();
$sortedProperties = $type->getProperties()->getSortedIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);

$isValueContainer = $type->isValueContainer();
$hasValueContainerParent = $type->hasValueContainerParent();

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
        'sortedProperties' => $sortedProperties,
    ]
);

// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $typeClassname; ?>

 * @package <?php echo $fqns; ?>

 */
<?php echo require_with(PHPFHIR_TEMPLATE_TYPES_DIR . '/definition.php', ['type' => $type, 'parentType' => $parentType]); ?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;<?php if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>

<?php endif; ?>

<?php if (0 !== count($sortedProperties)) :
    foreach($sortedProperties as $property) :
        if (!$property->isOverloaded()) :
            echo require_with(
                PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/constants.php',
                [
                        'property' => $property,
                ]
            );
        endif;
    endforeach;
?>

    /** @var string */
    protected $_xmlns = '<?php echo PHPFHIR_FHIR_XMLNS; ?>';

<?php
    foreach($sortedProperties as $property) :
        if (!$property->isOverloaded()) :
            echo require_with(
                PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/declaration.php',
                [
                        'config' => $config,
                        'property' => $property,
                ]
            );
        endif;
    endforeach;

endif;

echo require_with(
    PHPFHIR_TEMPLATE_VALIDATION_DIR . '/field_map.php',
    [
        'type' => $type,
        'sortedProperties' => $sortedProperties,
    ]
);

?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_METHODS_DIR . '/constructor.php',
        [
                'type' => $type,
                'sortedProperties' => $sortedProperties,
                'parentType' => $parentType,
        ]
    );

echo "\n";

echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . '/common.php',
    [
        'type' => $type,
        'parentType' => $type->getParentType(),
    ]
);

if ($type->isContainedType()) :
    echo require_with(
        PHPFHIR_TEMPLATE_METHODS_DIR . '/contained_type.php',
        [
            'type' => $type,
        ]
    );
endif;

 if (0 < count($sortedProperties)) :
    echo "\n";
    echo require_with(
        PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/methods.php',
        [
                'config' => $config,
                'type' => $type,
                'sortedProperties' => $sortedProperties,
        ]
    );
endif;?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_VALIDATION_DIR . '/method.php',
    [
            'type' => $type,
            'sortedProperties' => $sortedProperties
    ]
); ?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml.php',
    [
        'config' => $config,
        'type'     => $type,
        'typeKind' => $typeKind,
        'sortedProperties' => $sortedProperties,
        'parentType' => $parentType,
        'typeClassName' => $typeClassname,
    ]
); ?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json.php',
        [
                'type' => $type,
                'typeKind' => $typeKind,
                'sortedProperties' => $sortedProperties,
                'parentType' => $parentType,
        ]
); ?>

    /**
     * @return string
     */
    public function __toString()
    {
<?php if ($typeKind->isPrimitive()) :
        if ($type->getPrimitiveType()->is(PrimitiveTypeEnum::BOOLEAN)) : ?>
        return $this->getValue() ? PHPFHIRConstants::STRING_TRUE : PHPFHIRConstants::STRING_FALSE;
<?php else : ?>
        return (string)$this->getValue();
<?php endif; elseif ($typeKind->isOneOf([TypeKindEnum::_LIST, TypeKindEnum::PRIMITIVE_CONTAINER])) : ?>
        return (string)$this->getValue();
<?php else : ?>
        return self::FHIR_TYPE_NAME;
<?php endif; ?>
    }
}<?php return ob_get_clean();
