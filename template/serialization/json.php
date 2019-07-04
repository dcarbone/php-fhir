<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var null|\DCarbone\PHPFHIR\Definition\Type $parentType */

ob_start();
if ($typeKind->isPrimitive()) :
    echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json/primitive.php',
        [
            'type' => $type,
        ]
    );
elseif ($typeKind->isList()) :
    echo require_with(PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json/list.php', []);
elseif ($typeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :
    echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json/resource_container.php',
        [
            'sortedProperties' => $sortedProperties,
        ]
    );
else:
    echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json/default.php',
        [
            'sortedProperties' => $sortedProperties,
            'parentType' => $parentType
        ]
    );
endif;
return ob_get_clean();
