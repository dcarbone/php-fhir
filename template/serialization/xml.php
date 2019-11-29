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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var \DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var string $typeClassName */

$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/header.php',
    [
        'config' => $config,
        'type' => $type,
        'typeKind' => $typeKind,
        'sortedProperties' => $sortedProperties,
        'parentType' => $parentType,
        'typeClassName' => $typeClassName
    ]
);
if ($typeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
    if (null === $parentType) :
        echo require_with(
                PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/body_primitive_list.php',
                []
        );
    endif;
elseif ($typeKind->isPrimitiveContainer()) :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/body_primitive_container.php',
            []
    );
else :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/body_default.php',
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
// ResourceContainer and Resource.Inline types have their own special xml serialization mechanism
if ($typeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize/resource_container.php',
            [
                    'config' => $config,
                    'xmlName' => $xmlName,
                    'sortedProperties' => $sortedProperties,
            ]
    );
else :
    // everything else shares a common header
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize/header.php',
        [
                'config' => $config,
                'xmlName' => $xmlName,
        ]
    );

    if ($typeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
        if (null === $parentType) :
            // primitive and list types have a very simple serialization process
            echo require_with(
                PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize/body_primitive_list.php',
                []
            );
        endif;
    else :
        echo require_with(
                PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize/body_default.php',
                [
                        'type' => $type,
                        'parentType' => $parentType,
                        'sortedProperties' => $sortedProperties,
                ]
        );
    endif; ?>
<?php endif; ?>

        return $sxe;
    }
<?php return ob_get_clean();
