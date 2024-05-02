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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$getter = $property->getGetterName();
$propertyConstName = $property->getFieldConstantName();

ob_start();
if ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            foreach($vs as $v) {
                if (null === $v) {
                    continue;
                }
                $xw->startElement(self::<?php echo $propertyConstName; ?>);
                $xw->startElement($v->_getFhirTypeName());
                $v->xmlSerialize($e3, $config);
                $xw->endElement();
                $xw->endElement();
            }
        }
<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
            $xw->startElement(self::<?php echo $propertyConstName; ?>);
            $xw->startElement($v->_getFhirTypeName());
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
            $xw->endElement();
        }
<?php endif;
return ob_get_clean();
