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
    $setter = ($property->isCollection() ? 'add' : 'set').ucfirst($property->getName());
    if ($propertyTypeKind->isPrimitiveContainer()) : ?>
        $type = null;
        if (isset($attributes-><?php echo $propertyName; ?>)) {
            $type = new <?php echo $propertyTypeClassName; ?>((string)$v));
        }
        if (isset($children-><?php echo $propertyName; ?>)) {
            $type = <?php echo $propertyTypeClassName; ?>::xmlUnserialize($children-><?php echo $propertyName; ?>, $type);
        }
        if (null !== $type) {
            $this-><?php echo $setter; ?>($type);
        }
<?php else : ?>

<?php endif; ?>
<?php endforeach; ?>

<?php return ob_get_clean();