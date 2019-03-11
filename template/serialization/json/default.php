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
/** @var \DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

ob_start(); ?>
    /**
     * @return null|array
     */
    public function jsonSerialize()
    {
<?php if ($parentType) : ?>
        $a = parent::jsonSerialize();
        if (null === $a) {
            $a = [];
        }
<?php else : ?>
        $a = [];
<?php endif;
foreach ($sortedProperties as $property) :
    $propertyName = $property->getName();
    $propertyConstName = $property->getFieldConstantName();
    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $propertyTypeParentType = $propertyType->getParentType();
    $isCollection = $property->isCollection();
    $getter = 'get' . ucfirst($propertyName);
    if ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
        echo require 'default_property_primitive_list.php';
    elseif ($propertyTypeKind->isPrimitiveContainer()) :
        echo require 'default_property_primitive_container.php';
    else :
        echo require 'default_property_default.php';
    endif;
endforeach; ?>
        return [] === $a ? null : $a;
    }
<?php return ob_get_clean();
