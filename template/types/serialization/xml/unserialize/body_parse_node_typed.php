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
/** @var \DCarbone\PHPFHIR\Definition\Property $property */
/** @var int $i */

$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();

$requireArgs = [
    'config' => $config,
];

ob_start();

echo match ($propertyTypeKind) {
    TypeKind::RESOURCE_CONTAINER, TypeKind::RESOURCE_INLINE => require_with(
        __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_node_typed_resource_container.php',
        $requireArgs + [
            'property' => $property,
            'i' => $i,
        ]
    ),

    TypeKind::PHPFHIR_XHTML => require_with(
        __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_node_typed_xhtml.php',
        $requireArgs + [
            'property' => $property,
            'i' => $i,
        ]
    ),

    default => require_with(
        __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_node_typed_default.php',
        $requireArgs + [
            'property' => $property,
            'i' => $i,
        ]
    )
};


return ob_get_clean();