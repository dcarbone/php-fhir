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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyTypeClassName = $property->getValueFHIRType()->getClassName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyFieldConstExt = $property->getFieldConstantExtensionName();
$setter = $property->getSetterName();

ob_start(); ?>
        if (isset($data[self::<?php echo $propertyFieldConst; ?>]) || array_key_exists(self::<?php echo $propertyFieldConst; ?>, $data)) {
<?php if ($property->isCollection()) : ?>
            if (isset($data[self::<?php echo $propertyFieldConstExt; ?>]) && is_array($data[self::<?php echo $propertyFieldConstExt; ?>])) {
                $exts = $data[self::<?php echo $propertyFieldConstExt; ?>];
            } else {
                $exts = [];
            }
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                foreach($data[self::<?php echo $propertyFieldConst; ?>] as $i => $v) {
                    if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                        $this-><?php echo $setter; ?>($v);
                    } else {
                        $ext = (isset($exts[$i]) && is_array($exts[$i])) ? $exts[$i] : [];
                        if (is_array($v)) {
                            $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($v, $ext)));
                        } else {
                            $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $v] + $ext));
                        }
                    }
                }
            } elseif ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            } else<?php else :
echo "            ";
endif; ?>if ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $ext = (isset($data[self::<?php echo $propertyFieldConstExt; ?>]) && is_array($data[self::<?php echo $propertyFieldConstExt; ?>])) ? $data[self::<?php echo $propertyFieldConstExt; ?>] : [];
                if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($ext, $data[self::<?php echo $propertyFieldConst; ?>])));
                } else {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $data[self::<?php echo $propertyFieldConst; ?>]] + $ext));
                }
            }
        }
<?php
return ob_get_clean(); ?>
