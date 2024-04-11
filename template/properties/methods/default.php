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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $properties */

$isPrimitiveType = $type->getKind()->isOneOf(TypeKind::PRIMITIVE, TypeKind::_LIST);

ob_start();
foreach ($properties as $property) :
    if ($property->isOverloaded()) :
        continue;
    endif;
    if ($isPrimitiveType && $property->isValueProperty()) :
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'getter_primitive_value.php',
            [
                'config'   => $config,
                'type'     => $type,
                'property' => $property,
            ]
        );
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'getter_primitive_value.php',
            [
                'config'   => $config,
                'type' => $type,
                'property' => $property,
            ]
        );
        continue;
    endif;

    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $requireArgs = [
        'config'   => $config,
        'type'     => $type,
        'property' => $property,
    ];

    echo require_with(
        __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'getter_default.php',
        [
            'config'   => $config,
            'property' => $property,
        ]
    );

    echo "\n";

    if ($propertyTypeKind->isOneOf(TypeKind::PRIMITIVE, TypeKind::_LIST, TypeKind::PRIMITIVE_CONTAINER)) :
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'setter_primitive.php',
            $requireArgs
        );
    elseif ($propertyTypeKind->isOneOf(TypeKind::RESOURCE_CONTAINER, TypeKind::RESOURCE_INLINE)) :
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'setter_contained_resource.php',
            $requireArgs
        );
    else :
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'setter_default.php',
            $requireArgs
        );
    endif;
    if ($property->isCollection()) :
        echo "\n";
        if ($propertyTypeKind->isOneOf(TypeKind::RESOURCE_CONTAINER, TypeKind::RESOURCE_INLINE)) :
            echo require_with(
                __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'setter_contained_resource_collection.php',
                $requireArgs
            );
        else :
            echo require_with(
                __DIR__ . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'setter_collection.php',
                $requireArgs
            );
        endif;
    endif;

    echo "\n";

endforeach;
return substr(ob_get_clean(), 0, -1); // trim off final \n