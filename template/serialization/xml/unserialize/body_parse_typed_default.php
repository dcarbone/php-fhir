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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyType = $property->getValueFHIRType();
$propertyTypeClassName = $property->getMemberOf()->getImports()->getImportByType($propertyType);
$propertyName = $property->getName();
$setter = $property->getSetterName();

ob_start(); ?>
        if (isset($children-><?php echo $propertyName; ?>)) {
<?php if ($property->isCollection()) : ?>
            foreach($children-><?php echo $propertyName; ?> as $child) {
                $type-><?php echo $setter; ?>(<?php echo $propertyTypeClassName; ?>::xmlUnserialize($child));
            }
<?php else : ?>
            $type-><?php echo $setter; ?>(<?php echo $propertyTypeClassName; ?>::xmlUnserialize($children-><?php echo $propertyName; ?>));
<?php endif; ?>
        }<?php if (!$property->isCollection() && $propertyType->getKind()->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST, TypeKindEnum::PRIMITIVE_CONTAINER])) : ?>

        if (isset($attributes-><?php echo $propertyName; ?>)) {
            $pt = $type-><?php echo $property->getGetterName(); ?>();
            if (null !== $pt) {
                $pt->setValue((string)$attributes-><?php echo $propertyName; ?>);
            } else {
                $type-><?php echo $setter; ?>((string)$attributes-><?php echo $propertyName; ?>);
            }
        }<?php endif; ?>

<?php return ob_get_clean();
