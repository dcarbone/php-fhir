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

/** @var bool $isContainedType */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

ob_start(); ?>
    /**
     * @return array
     */
    public function jsonSerialize()
    {
<?php if ($parentType) : ?>
        $a = parent::jsonSerialize();
<?php else : ?>
        $a = [];
<?php endif;

if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>
        if ([] !== ($vs = $this->_getFHIRComments())) {
            $a[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS] = $vs;
        }
<?php endif;
foreach ($sortedProperties as $property) :
    if ($property->isOverloaded()) :
        continue;
    endif;
    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $requireArgs = [
            'hasValueContainerParent' => $propertyType->hasValueContainerParent(),
            'isValueProperty' => $property->isValueProperty(),
            'isCollection' => $property->isCollection(),
            'getter' => 'get' . ucfirst($property->getName()),
            'propertyConstName' => $property->getFieldConstantName(),
            'propertyConstNameExt' => "{$property->getFieldConstantName()}_EXT"
    ];
    if ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) :
        echo require_with(
            __DIR__ . '/default_property_primitive_list.php',
                $requireArgs
        );
    elseif ($propertyType->isValueContainer() || $propertyType->hasValueContainerParent()) :
        echo require_with(
            __DIR__ . '/default_property_value_container.php',
            $requireArgs
        );
    else :
        echo require_with(__DIR__ . '/default_property_default.php', $requireArgs);
    endif;
endforeach; ?>
        return <?php if ($isContainedType) : ?>[<?php  echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE => $this->_getResourceType()] + <?php endif; ?>$a;
    }
<?php return ob_get_clean();
