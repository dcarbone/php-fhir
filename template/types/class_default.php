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

use DCarbone\PHPFHIR\Enum\TypeKind;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassname = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$localProperties = $type->getLocalProperties()->localPropertiesIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);

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

 * Class <?php echo $typeClassname; ?>

 * @package <?php echo $fqns; ?>

 */
<?php
echo require_with(
    PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'definition.php',
    [
        'config' => $config,
        'type' => $type,
        'parentType' => $parentType
    ]
);
?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;
<?php

if (0 !== count($localProperties)) {

    echo "\n";

    foreach ($localProperties as $property) {
        echo require_with(
            PHPFHIR_TEMPLATE_TYPES_PROPERTIES_DIR . DIRECTORY_SEPARATOR . 'constants.php',
            [
                'config' => $config,
                'property' => $property,
            ]
        );
    }

    echo "\n";

    foreach ($localProperties as $property) {
        echo require_with(
            PHPFHIR_TEMPLATE_TYPES_PROPERTIES_DIR . DIRECTORY_SEPARATOR . 'declaration.php',
            [
                'config' => $config,
                'property' => $property,
            ]
        );
    }

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'field_map.php',
        [
            'config' => $config,
            'type' => $type,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'primitive_xml_location_map.php',
        [
            'config' => $config,
            'type' => $type,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'constructor.php',
        [
            'config' => $config,
            'type' => $type,
            'properties' => $localProperties,
            'parentType' => $parentType,
        ]
    );
}

echo "\n";

echo require_with(
    PHPFHIR_TEMPLATE_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'common.php',
    [
        'config' => $config,
        'type' => $type,
        'parentType' => $type->getParentType(),
    ]
);

if ($type->isContainedType()) {
    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'contained_type.php',
        [
            'config' => $config,
            'type' => $type,
        ]
    );
}

if (0 < count($localProperties)) {
    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_PROPERTIES_DIR . DIRECTORY_SEPARATOR . 'methods.php',
        [
            'config' => $config,
            'type' => $type,
            'properties' => $localProperties,
        ]
    );
    
    echo "\n";
    
    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods.php',
        [
            'config' => $config,
            'type' => $type,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml.php',
        [
            'config' => $config,
            'type'     => $type,
            'typeKind' => $typeKind,
            'parentType' => $parentType,
            'typeClassName' => $typeClassname,
        ]
    );

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_TYPES_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'json.php',
        [
            'config' => $config,
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
<?php if ($typeKind === TypeKind::PRIMITIVE) : ?>
        return $this->getFormattedValue();
<?php elseif ($typeKind->isOneOf(TypeKind::LIST, TypeKind::PRIMITIVE_CONTAINER)) : ?>
        return (string)$this->getValue();
<?php else : ?>
        return self::FHIR_TYPE_NAME;
<?php endif; ?>
    }
<?php endif; ?>}<?php return ob_get_clean();
