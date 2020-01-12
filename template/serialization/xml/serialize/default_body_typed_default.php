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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyType = $property->getValueFHIRType();
$propertyConstName = $property->getFieldConstantName();
$getter = $property->getGetterName();

ob_start();
if ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            foreach($vs as $v) {
                if (null === $v) {
                    continue;
                }
<?php if ($propertyType->getKind()->isRaw()) : ?>
                $sxe->addChild(self::<?php echo $propertyConstName; ?>, (string)$v, $v->_getFHIRXMLNamespace()));
<?php else : ?>
                $v->xmlSerialize($sxe->addChild(self::<?php echo $propertyConstName; ?>, null, $v->_getFHIRXMLNamespace()));
<?php endif; ?>
            }
        }
<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
<?php if ($propertyType->getKind()->isRaw()) : ?>
            $sxe->addChild(self::<?php echo $propertyConstName; ?>, (string)$v, $v->_getFHIRXMLNamespace());
<?php elseif ($propertyType->hasPrimitiveParent() || $propertyType->getKind()->isPrimitive()) : ?>
            $sxe->addAttribute(self::<?php echo $propertyConstName; ?>, (string)$v);
<?php elseif ($propertyType->isValueContainer()) : /* TODO: improve so we don't double-print value element(s) */ ?>
            $sxe->addAttribute(self::<?php echo $propertyConstName; ?>, (string)$v);
            $v->xmlSerialize($sxe->addChild(self::<?php echo $propertyConstName; ?>, null, $v->_getFHIRXMLNamespace()));
<?php else :?>
            $v->xmlSerialize($sxe->addChild(self::<?php echo $propertyConstName; ?>, null, $v->_getFHIRXMLNamespace()));
<?php endif; ?>
        }
<?php endif;
return ob_get_clean();
