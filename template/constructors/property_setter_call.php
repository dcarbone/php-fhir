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
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$isCollection = $property->isCollection();
$propertyName = $property->getName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();
$propertyTypeClassName = $propertyType->getClassName();
$setter = ($isCollection ? 'add' : 'set') . ucfirst($propertyName);

ob_start(); ?>
        if (isset($data[self::<?php echo $propertyFieldConst; ?>])) {
<?php if ($isCollection) : ?>
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                foreach($data[self::<?php echo $propertyFieldConst; ?>] as $item) {
                    if ($item instanceof <?php echo $propertyTypeClassName; ?>) {
                        $this-><?php echo $setter; ?>($item);
                    } else {
                        $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($item));
                    }
                }
            } else if ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($data[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php elseif ($propertyTypeKind->isOneOf([TypeKindEnum::PRIMITIVE, TypeKindEnum::_LIST, TypeKindEnum::PRIMITIVE_CONTAINER])) : ?>
            $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
<?php else : ?>
            if ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($data[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php endif; ?>
        }
<?php return ob_get_clean();
