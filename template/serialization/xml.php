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
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKind $typeKind */
/** @var \DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var string $typeClassName */

$xmlName = NameUtils::getTypeXMLElementName($type);
$localProperties = $type->getProperties()->localPropertiesIterator();
$properties = $type->getAllPropertiesIterator();

ob_start();
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'unserialize' . DIRECTORY_SEPARATOR . 'header.php',
    [
        'config' => $config,
        'type' => $type,
        'typeKind' => $typeKind,
        'parentType' => $parentType,
        'typeClassName' => $typeClassName
    ]
);

if (0 < count($properties)) :
    echo require_with(
        PHPFHIR_TEMPLATE_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'unserialize' . DIRECTORY_SEPARATOR . 'body.php',
        [
            'config' => $config,
            'type' => $type,
            'properties' => $properties,
        ]
    );
endif;
?>
        return $type;
    }

<?php
// serialize portion
// ResourceContainer and Resource.Inline types have their own special xml serialization mechanism
if ($typeKind->isOneOf(TypeKind::RESOURCE_CONTAINER, TypeKind::RESOURCE_INLINE)) :
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'serialize' . DIRECTORY_SEPARATOR . 'resource_container.php',
            [
                'config' => $config,
                'type' => $type,
            ]
    );
else :
    // everything else shares a common header
    // header is always output as it is what creates the simplexml instance
    echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'serialize' . DIRECTORY_SEPARATOR . 'default_header.php',
        [
            'config' => $config,
            'parentType' => $parentType,
        ]
    );

    if (0 < count($localProperties)) :
        echo require_with(
            PHPFHIR_TEMPLATE_SERIALIZATION_DIR . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'serialize' . DIRECTORY_SEPARATOR . 'default_body.php',
            [
                'config' => $config,
                'type' => $type,
                'parentType' => $parentType,
                'localProperties' => $localProperties,
            ]
        );
    endif;
endif; ?>
        return $element;
    }
<?php return ob_get_clean();
