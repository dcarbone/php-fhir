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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$isCollection = $property->isCollection();
$propertyFieldConst = $property->getFieldConstantName();
$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();
$requireArgs = [
        'propertyTypeKind' => $propertyTypeKind,
        'isCollection' => $isCollection,
        'propertyFieldConst' => $propertyFieldConst,
        'propertyTypeClassName' => (string)$type->getImports()->getImportByType($propertyType),
        'setter' => ($isCollection ? 'add' : 'set') . ucfirst($property->getName()),
];

ob_start(); ?>
        if (isset($data[self::<?php echo $propertyFieldConst; ?>])) {
<?php if ($propertyTypeKind->isPrimitive() || $propertyType->hasPrimitiveParent()) :
    echo require_with(
            __DIR__ . '/property_setter_primitive.php',
            $requireArgs
    );
elseif ($propertyType->isValueContainer() || $propertyType->hasValueContainerParent()) :
    echo require_with(
            __DIR__ . '/property_setter_value_container.php',
            $requireArgs + ['propertyFieldConstExt' => $property->getFieldConstantExtensionName()]
    );
else :
    echo require_with(
            __DIR__ . '/property_setter_default.php',
            $requireArgs + ['propertyFieldConstExt' => $property->getFieldConstantExtensionName()]
    );
endif;
if ($type->getKind()->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) : ?>
            return;
<?php endif; ?>
        }
<?php
unset($returnAfterCall);
return ob_get_clean();
