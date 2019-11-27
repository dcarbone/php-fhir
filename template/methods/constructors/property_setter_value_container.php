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

/** @var string $propertyFieldConst */
/** @var string $propertyFieldConstExt */
/** @var bool $isCollection */
/** @var string $setter */

ob_start(); ?>
            $ext = (isset($data[self::<?php echo $propertyFieldConstExt; ?>]) && is_array($data[self::<?php echo $propertyFieldConstExt; ?>]))
                ? $data[self::<?php echo $propertyFieldConstExt; ?>]
                : null;
<?php if ($isCollection) : ?>
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                foreach($data[self::<?php echo $propertyFieldConst; ?>] as $i => $v) {
                    if (null === $v) {
                        continue;
                    }
                    if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                        $this-><?php echo $setter; ?>($v);
                    } elseif (null !== $ext && isset($ext[$i]) && is_array($ext[$i])) {
                        if (is_scalar($v)) {
                            $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $v] + $ext[$i]));
                        } elseif (is_array($v)) {
                            $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($v, $ext[$i])));
                        }
                    } else {
                        $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($v));
                    }
                }
            } elseif ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            } elseif (null !== $ext && is_scalar($data[self::<?php echo $propertyFieldConst; ?>])) {
                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $data[self::<?php echo $propertyFieldConst; ?>]] + $ext));
            } else {
                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($data[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php else : ?>
            if ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            } elseif (null !== $ext) {
                if (is_scalar($data[self::<?php echo $propertyFieldConst; ?>])) {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $data[self::<?php echo $propertyFieldConst; ?>]] + $ext));
                } else if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                    $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($ext, $data[self::<?php echo $propertyFieldConst; ?>])));
                }
            } else {
                $this-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($data[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php endif;
return ob_get_clean(); ?>
