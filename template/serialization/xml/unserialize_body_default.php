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
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

ob_start();
foreach ($sortedProperties as $property) :
    $propertyName = $property->getName();
    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $propertyTypeClassName = $propertyType->getClassName();
    $isCollection = $property->isCollection();
    $setter = ($isCollection ? 'add' : 'set') . ucfirst($propertyName);
    if ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST, TypeKindEnum::PRIMITIVE_CONTAINER])) :
        echo require 'unserialize_body_default_property_setter_primitive_list_container.php';
    else :
        echo require 'unserialize_body_default_property_setter_default.php';
    endif;
endforeach;
return ob_get_clean();