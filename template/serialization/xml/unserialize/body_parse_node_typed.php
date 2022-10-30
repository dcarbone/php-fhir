<?php declare(strict_types=1);

/*
 * Copyright 2018-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */
/** @var int $i */

$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();

$requireArgs = [
    'config' => $config,
];

ob_start();

if ($propertyTypeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :
    echo require_with(
        __DIR__ . '/body_parse_typed_resource_container.php',
        $requireArgs + [
            'property' => $property,
            'i' => $i,
        ]
    );
else :
    echo require_with(
        __DIR__ . '/body_parse_typed_default.php',
        $requireArgs + [
            'property' => $property,
            'i' => $i,
        ]
    );
endif;

return ob_get_clean();