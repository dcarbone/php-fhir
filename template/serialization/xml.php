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

ob_start();
// unserialize portion
echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_header.php';
if ($typeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
    echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_body_primitive_list.php';
elseif ($typeKind->isPrimitiveContainer()) :
    echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_body_primitive_container.php';
elseif ($typeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :

else :
    echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_body_default.php';
endif; ?>
        return $type;
    }

<?php
// serialize portion
echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_header.php';
if ($typeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
    echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_body_primitive_list.php';
elseif ($typeKind->isPrimitiveContainer()) :
    echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_body_primitive_container.php';
elseif ($typeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) :

else :
    echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_body_default.php';
endif; ?>
        return $returnSXE ? $sxe : $sxe->saveXML();
    }
<?php return ob_get_clean();
