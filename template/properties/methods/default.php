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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

$isPrimitiveType = $type->getKind()->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST]);

ob_start();
foreach ($sortedProperties as $property) :
    if ($property->isOverloaded()) :
        continue;
    endif;
    if ($isPrimitiveType && $property->isValueProperty()) :
        echo require_with(
            __DIR__ . '/default/getter_primitive_value.php',
            [
                'type'     => $type,
                'property' => $property,
            ]
        );
        echo require_with(
            __DIR__ . '/default/setter_primitive_value.php',
            [
                'type' => $type,
                'property' => $property,
            ]
        );
        continue;
    endif;

    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $requireArgs = [
        'type'     => $type,
        'property' => $property,
    ];

    echo require_with(
        __DIR__ . '/default/getter_default.php',
        [
            'config'   => $config,
            'property' => $property,
        ]
    );

    echo "\n";

    if ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST, TypeKindEnum::PRIMITIVE_CONTAINER])) :
        echo require_with(
            __DIR__ . '/default/setter_primitive.php',
            $requireArgs
        );
    elseif ($propertyTypeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :
        echo require_with(
            __DIR__ . '/default/setter_contained_resource.php',
            $requireArgs + ['config' => $config]
        );
    else :
        echo require_with(
            __DIR__ . '/default/setter_default.php',
            $requireArgs + ['config' => $config]
        );
    endif;
    if ($property->isCollection()) :
        echo "\n";
        if ($propertyTypeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :
            echo require_with(
                __DIR__ . '/default/setter_contained_resource_collection.php',
                $requireArgs + ['config' => $config]
            );
        else :
            echo require_with(
                __DIR__ . '/default/setter_collection.php',
                $requireArgs + ['config' => $config]
            );
        endif;
    endif;

    echo "\n";

endforeach;
return substr(ob_get_clean(), 0, -1); // trim off final \n