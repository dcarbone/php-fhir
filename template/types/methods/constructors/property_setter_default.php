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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */
/** @var int $propertyIndex */

$propertyTypeClassName = $property->getValueFHIRType()->getClassName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyFieldConstExt = $property->getFieldConstantExtensionName();
$setter = $property->getSetterName();

ob_start();
if (0 === $propertyIndex) : ?>
            if <?php else : ?> else if <?php endif; ?>(self::<?php echo $propertyFieldConst; ?> === $field) {
<?php if ($property->isCollection()) : ?>
                if (is_array($value)) {
                    foreach($value as $v) {
                        if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                            $this-><?php echo $setter; ?>($v);
                        } else {
                            $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($v));
                        }
                    }
                } elseif ($value instanceof <?php echo $propertyTypeClassName; ?>) {
                    $this-><?php echo $setter; ?>($value);
                } else {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($value));
                }
<?php else : ?>
                if ($value instanceof <?php echo $propertyTypeClassName; ?>) {
                    $this-><?php echo $setter; ?>($value);
                } else {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($value));
                }
<?php endif; ?>
            }<?php
return ob_get_clean(); ?>
