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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyTypeClassname = $property->getValueFHIRType()->getClassName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyFieldConstExt = $property->getFieldConstantExtensionName();
$getter = $property->getGetterName();

ob_start();
if ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            $a[self::<?php echo $propertyFieldConst; ?>] = [];
            $encs = [];
            $encValued = false;
            foreach ($vs as $v) {
                if (null === $v) {
                    continue;
                }
                $a[self::<?php echo $propertyFieldConst; ?>][] = $v->getValue();
                $enc = $v->jsonSerialize();
                $cnt = count($enc);
                if (0 === $cnt || (1 === $cnt && (isset($enc[<?php echo $propertyTypeClassname; ?>::FIELD_VALUE]) || array_key_exists(<?php echo $propertyTypeClassname; ?>::FIELD_VALUE, $enc)))) {
                    $encs[] = null;
                } else {
                    unset($enc[<?php echo $propertyTypeClassname; ?>::FIELD_VALUE]);
                    $encs[] = $enc;
                    $encValued = true;
                }
            }
            if ($encValued) {
                $a[self::<?php echo $propertyFieldConstExt; ?>] = $encs;
            }
        }
<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
            $a[self::<?php echo $propertyFieldConst; ?>] = $v->getValue();
            $enc = $v->jsonSerialize();
            $cnt = count($enc);
            if (0 < $cnt && (1 !== $cnt || (1 === $cnt && !array_key_exists(<?php echo $propertyTypeClassname; ?>::FIELD_VALUE, $enc)))) {
                unset($enc[<?php echo $propertyTypeClassname; ?>::FIELD_VALUE]);
                $a[self::<?php echo $propertyFieldConstExt; ?>] = $enc;
            }
        }
<?php endif;
return ob_get_clean();