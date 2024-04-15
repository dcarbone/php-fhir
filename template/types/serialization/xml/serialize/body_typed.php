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

$propertyTypeKind = $property->getValueFHIRType()->getKind();

ob_start();
if ($propertyTypeKind->isOneOf(TypeKind::RESOURCE_CONTAINER, TypeKind::RESOURCE_INLINE)) :
    echo require_with(
        __DIR__ . DIRECTORY_SEPARATOR . 'body_typed_resource_container.php',
        [
            'config' => $config,
            'property' => $property,
        ]
    );
else :
    echo require_with(
        __DIR__ . DIRECTORY_SEPARATOR . 'body_typed_default.php',
        [
            'config' => $config,
            'property' => $property,
        ]
    );
endif;
return ob_get_clean();