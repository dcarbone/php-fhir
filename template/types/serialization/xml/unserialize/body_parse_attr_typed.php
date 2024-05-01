<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKind;

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyType = $property->getValueFHIRType();
$propertyTypeClassName = $property->getMemberOf()->getImports()->getImportByType($propertyType);
$propertyName = $property->getName();
$propertyConst = $property->getFieldConstantName();
$setter = $property->getSetterName();

ob_start();
if ($propertyType->getKind()->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST, TypeKind::PRIMITIVE_CONTAINER)) : ?>
        $n = $element->attributes[self::<?php echo $propertyConst; ?>];
        if (null !== $n) {
<?php if (!$property->isCollection()) : ?>
            $pt = $type-><?php echo $property->getGetterName(); ?>();
            if (null !== $pt) {
                $pt->setValue((string)$n);
            } else {
                $type-><?php echo $setter; ?>((string)$n);
            }
<?php else : ?>
            $type-><?php echo $setter; ?>((string)$n);
<?php endif; ?>
        }
<?php endif;
return ob_get_clean();
