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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

$isPrimitiveType = $type->getKind()->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST]);

ob_start();

if ($type->getKind()->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
    echo require_with(
        PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/methods/primitive.php',
        ['type' => $type]
    );
else :
    echo require_with(
        PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/methods/default.php',
        [
            'config'           => $config,
            'type'             => $type,
            'sortedProperties' => $sortedProperties,
        ]
    );
endif;

return ob_get_clean();