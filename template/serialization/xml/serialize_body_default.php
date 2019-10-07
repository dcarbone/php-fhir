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

/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */

ob_start();
if (null !== $parentType) : ?>
        parent::xmlSerialize($sxe);
<?php endif;
foreach ($sortedProperties as $property) :
    $propertyName = $property->getName();
    $propertyConstName = $property->getFieldConstantName();
    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $isCollection = $property->isCollection();
    $getter = 'get' . ucfirst($propertyName);
    $requireArgs = [
        'isCollection' => $isCollection,
        'propertyConstName' => $propertyConstName,
        'getter' => $getter
    ];
    if ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
        echo require_with(
            __DIR__ . '/serialize_body_default_property_primitive_list.php',
            $requireArgs
        );
    elseif ($propertyTypeKind->isPrimitiveContainer()) :
        echo require_with(
            __DIR__ . '/serialize_body_default_property_primitive_container.php',
            $requireArgs
        );
    elseif ($propertyTypeKind->isResourceContainer()) :
        echo require_with(
            __DIR__ . '/serialize_body_default_property_resource_container.php',
            $requireArgs
        );
    else :
        echo require_with(
            __DIR__ . '/serialize_body_default_property_default.php',
            $requireArgs
        );
    endif;
endforeach;
return ob_get_clean();
