<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$typeKind = $type->getKind();

ob_start();

echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR
    . DIRECTORY_SEPARATOR
    . 'json'
    . DIRECTORY_SEPARATOR
    . 'unserialize'
    . DIRECTORY_SEPARATOR
    . 'header.php',
    [
        'version' => $version,
        'type'     => $type,
    ]
);

echo "\n";

echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR
    . DIRECTORY_SEPARATOR
    . 'json'
    . DIRECTORY_SEPARATOR
    . 'unserialize'
    . DIRECTORY_SEPARATOR
    . 'body.php',
    [
        'version' => $version,
        'type'     => $type,
    ]
);

echo "\n";

if ($typeKind->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) :
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR
        . DIRECTORY_SEPARATOR
        . 'json'
        . DIRECTORY_SEPARATOR
        . 'serialize'
        . DIRECTORY_SEPARATOR
        . 'primitive.php',
        [
            'version' => $version,
            'type'     => $type,
        ]
    );
else:
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR
        . DIRECTORY_SEPARATOR
        . 'json'
        . DIRECTORY_SEPARATOR
        . 'serialize'
        . DIRECTORY_SEPARATOR
        . 'default.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );
endif;
return ob_get_clean();
