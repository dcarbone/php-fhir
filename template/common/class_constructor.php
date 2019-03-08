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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

ob_start(); ?>
    /**
     * <?php echo $typeClassName; ?> Constructor

     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if ([] === $data) {
            return;
        }
<?php foreach($sortedProperties as $property) :
    $isCollection = $property->isCollection();
    $propName = $property->getName();
    $const = $property->getFieldConstantName();
    $propType = $property->getValueFHIRType();
    $propTypeKind = $propType->getKind();
    $propTypeClassname = $propType->getClassName();
    $setter = ($isCollection ? 'add' : 'set').ucfirst($propName);?>
        if (isset($data[self::<?php echo $const; ?>])) {
<?php if ($propTypeKind->is(TypeKindEnum::PRIMITIVE)) : ?>
            if (is_scalar($data[
<?php elseif ($propTypeKind->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE])) : ?>

<?php else : ?>
<?php if ($isCollection): ?>
            if (is_array($data[self::<?php echo $const; ?>])) {
                foreach($data[self::<?php echo $const; ?>] as $item) {
                    $this-><?php echo $setter; ?>($item);
                }
            } else {
                $this-><?php echo $setter; ?>($data[self::<?php echo $const; ?>]);
            }
<?php else : ?>
            $this-><?php echo $setter; ?>($data[self::<?php echo $const; ?>]);
<?php endif; ?>
<? endif; ?>
        }
<?php endforeach; ?>
    }

<?php return ob_get_clean();
