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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var \DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var string $typeClassName */

$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_header.php',
    [
        'type' => $type,
        'typeKind' => $typeKind,
        'sortedProperties' => $sortedProperties,
        'parentType' => $parentType,
        'typeClassName' => $typeClassName
    ]
);
if ($typeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
    echo require_with(PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_body_primitive_list.php', []);
elseif ($typeKind->isPrimitiveContainer()) :
    echo require_with(PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_body_primitive_container.php', []);
else :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_body_default.php',
            [
                    'sortedProperties' => $sortedProperties,
                    'typeImports' => $type->getImports(),
            ]
    );
endif; ?>
        return $type;
    }

<?php
// serialize portion
if ($typeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_resource_container.php',
            [
                    'xmlName' => $xmlName,
                    'sortedProperties' => $sortedProperties,
            ]
    );
else :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_header.php',
        [
                'xmlName' => $xmlName,
        ]
    );
    if ($typeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
        echo require_with(PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_body_primitive_list.php', []);
    elseif ($typeKind->isPrimitiveContainer()) :
        echo require_with(
                PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_body_primitive_container.php',
            [
                    'parentType' => $parentType
            ]
        );
    else :
        echo require_with(
                PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_body_default.php',
                [
                        'sortedProperties' => $sortedProperties,
                ]
        );
    endif; ?>
<?php endif; ?>
        return $sxe;
    }
<?php return ob_get_clean();
