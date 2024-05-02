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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyType = $property->getValueFHIRType();
$propertyConstName = $property->getFieldConstantName();
$getter = $property->getGetterName();

ob_start();
if ($property->isCollection()) : // collection fields ?>
        foreach ($this-><?php echo $getter; ?>() as $v) {
            $xw->startElement(self::<?php echo $propertyConstName; ?>);
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php else : // single fields ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
<?php if ($propertyType->hasPrimitiveParent() || $propertyType->getKind() === TypeKind::PRIMITIVE) : ?>
            $xw->writeAttribute(self::<?php echo $propertyConstName; ?>, $v->getFormattedValue());
<?php else : ?>
            $xw->startElement(self::<?php echo $propertyConstName; ?>);
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
<?php endif; ?>
        }
<?php endif;
return ob_get_clean();
