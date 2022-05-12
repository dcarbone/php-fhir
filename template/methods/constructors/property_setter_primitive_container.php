<?php declare(strict_types=1);

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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyTypeClassName = $property->getValueFHIRType()->getClassName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyFieldConstExt = $property->getFieldConstantExtensionName();
$setter = $property->getSetterName();

// these types are a pain in the ass

ob_start(); ?>
        if (isset($data[self::<?php echo $propertyFieldConst; ?>]) || isset($data[self::<?php echo $propertyFieldConstExt; ?>])) {
            $value = isset($data[self::<?php echo $propertyFieldConst; ?>]) ? $data[self::<?php echo $propertyFieldConst; ?>] : null;
            $ext = (isset($data[self::<?php echo $propertyFieldConstExt; ?>]) && is_array($data[self::<?php echo $propertyFieldConstExt; ?>])) ? $ext = $data[self::<?php echo $propertyFieldConstExt; ?>] : $ext = [];
            if (null !== $value) {
                if ($value instanceof <?php echo $propertyTypeClassName; ?>) {
                    $this-><?php echo $setter; ?>($value);
                } else <?php if ($property->isCollection()) : ?>if (is_array($value)) {
                    foreach($value as $i => $v) {
                        if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                            $this-><?php echo $setter; ?>($v);
                        } else {
                            $iext = (isset($ext[$i]) && is_array($ext[$i])) ? $ext[$i] : [];
                            if (is_array($v)) {
                                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($v, $iext)));
                            } else {
                                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $v] + $iext));
                            }
                        }
                    }
                } else<?php endif; ?>if (is_array($value)) {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($ext, $value)));
                } else {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $value] + $ext));
                }
            } elseif ([] !== $ext) {
<?php if ($property->isCollection()) : ?>
                foreach($ext as $iext) {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($iext));
                }
<?php else : ?>
                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($ext));
<?php endif; ?>
            }
        }
<?php
return ob_get_clean(); ?>
