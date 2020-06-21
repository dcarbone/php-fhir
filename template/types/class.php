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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassname = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$directProperties = $type->getProperties()->getDirectIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);

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

 * Class <?php echo $typeClassname; ?>

 * @package <?php echo $fqns; ?>

 */
<?php echo require_with(PHPFHIR_TEMPLATE_TYPES_DIR . '/definition.php', ['type' => $type, 'parentType' => $parentType]); ?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;<?php if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>

<?php endif; ?>

<?php if (0 !== count($directProperties)) :
    foreach($directProperties as $property) :
        echo require_with(
            PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/constants.php',
            [
                    'property' => $property,
            ]
        );
    endforeach;
endif; ?>

    /** @var string */
    private $_xmlns = '';

<?php if (0 !== count($directProperties)) :
    foreach($directProperties as $property) :
        echo require_with(
            PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/declaration.php',
            [
                    'config' => $config,
                    'property' => $property,
            ]
        );
    endforeach;
endif;

echo require_with(
    PHPFHIR_TEMPLATE_VALIDATION_DIR . '/field_map.php',
    [
        'type' => $type,
    ]
);

echo "\n";

echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . '/constructor.php',
    [
            'type' => $type,
            'properties' => $directProperties,
            'parentType' => $parentType,
    ]
);

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

 if (0 < count($directProperties)) :
    echo "\n";
    echo require_with(
        PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/methods.php',
        [
                'config' => $config,
                'type' => $type,
                'properties' => $directProperties,
        ]
    );
endif; ?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_VALIDATION_DIR . '/methods.php',
    [
            'type' => $type,
    ]
); ?>

<?php echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml.php',
    [
        'config' => $config,
        'type'     => $type,
        'typeKind' => $typeKind,
        'parentType' => $parentType,
        'typeClassName' => $typeClassname,
    ]
);

if (0 < count($directProperties)) :
    echo "\n";
    echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json.php',
        [
            'type' => $type,
        ]
    );
    echo "\n";
endif;

if (!$type->hasPrimitiveParent()) :
?>

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
<?php endif; ?>}<?php return ob_get_clean();
