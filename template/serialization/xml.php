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
$directProperties = $type->getProperties()->getDirectSortedIterator();

ob_start();
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/header.php',
    [
        'config' => $config,
        'type' => $type,
        'typeKind' => $typeKind,
        'parentType' => $parentType,
        'typeClassName' => $typeClassName
    ]
);

if (0 < count($directProperties)) :
    echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize/body.php',
        [
            'type' => $type,
        ]
    );
endif;
?>
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
                    'type' => $type,
            ]
    );
else :
    // everything else shares a common header
    // header is always output as it is what creates the simplexml instance
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize/default_header.php',
        [
                'config' => $config,
                'parentType' => $parentType,
        ]
    );

    $directProperties = $type->getProperties()->getDirectSortedIterator();
    if (0 < count($directProperties)) :
        echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize/default_body.php',
            [
                'type' => $type,
                'parentType' => $parentType,
                'directProperties' => $directProperties,
            ]
        );
    endif;
endif; ?>
        return $sxe;
    }
<?php return ob_get_clean();
