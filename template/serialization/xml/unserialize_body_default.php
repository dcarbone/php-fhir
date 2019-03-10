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

ob_start(); ?>

<?php foreach($sortedProperties as $property) :
    $propertyName = $property->getName();
    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    $propertyTypeClassName = $propertyType->getClassName();
    $setter = ($property->isCollection() ? 'add' : 'set').ucfirst($property->getName()); ?>
        $t = null;
<?php if ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST])) : ?>
        if (isset($attributes-><?php echo $propertyName; ?>)) {
            $t = new <?php echo $propertyTypeClassName; ?>((string)$attributes-><?php echo $propertyName; ?>);
        } elseif (isset($children-><?php echo $propertyName; ?>)) {
            $t = <?php echo $propertyTypeClassName; ?>::xmlUnserialize($children-><?php echo $propertyName; ?>, $t);
        }
<?php elseif ($propertyTypeKind->isPrimitiveContainer()) : ?>
        if (isset($children-><?php echo $propertyName; ?>)) {
            $t = <?php echo $propertyTypeClassName; ?>::xmlUnserialize($children-><?php echo $propertyName; ?>, $t);
        }
        if (isset($attributes-><?php echo $propertyName; ?>)) {
            if (null === $t) {
                $t = new <?php echo $propertyTypeClassName; ?>((string)$attributes-><?php echo $propertyName; ?>);
            } else {
                $t->setValue((string)$attributes-><?php echo $propertyName; ?>);
            }
        }
<?php else : ?>

<?php endif; ?>
        if (null !== $t) {
            $type-><?php echo $setter; ?>($t);
        }
<?php endforeach; ?>

<?php return ob_get_clean();