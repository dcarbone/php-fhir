<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

ob_start();

// first, marshal attribute values
foreach ($type->getProperties()->getIterator() as $property) :
    $propType = $property->getValueFHIRType();

    $propTypeKind = $propType->getKind();

    if ($propType->hasPrimitiveOrListParent() || $propType->isPrimitiveOrListType()) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $v->_getFormattedValue());
                break;
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ATTRIBUTE) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->_getFormattedValue());
        }
<?php   endif;
    elseif ($propType->isPrimitiveContainer()) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
           foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ATTRIBUTE) {
                    $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $v->getValue()?->_getFormattedValue());
                    break;
                }
            }
        }
<?php   else: ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ATTRIBUTE) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->getValue()?->_getFormattedValue());
        }
<?php   endif;
    endif;
endforeach;

// next, marshal parent attribute & element values
if ($type->hasConcreteParent()) : ?>
        parent::xmlSerialize($xw, $config);
<?php endif;

// finally, marshal local element values
foreach ($type->getProperties()->getIterator() as $property) :
    $propType = $property->getValueFHIRType();
    $propTypeKind = $propType->getKind();

    if ($propTypeKind->isResourceContainer($version)) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                $xw->startElement($v->_getFHIRTypeName());
                $v->xmlSerialize($xw, $config);
                $xw->endElement();
                $xw->endElement();
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->startElement($this-><?php echo $property->getName(); ?>->_getFHIRTypeName());
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config);
            $xw->endElement();
            $xw->endElement();
        }
<?php    endif;
    elseif ($propType->hasPrimitiveOrListParent() || $propType->isPrimitiveOrListType()) : ?>
<?php    if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ELEMENT) {
                    $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                    $xw->writeAttribute($v::FIELD_VALUE, $v->_getFormattedValue());
                    $xw->endElement();
                }
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ELEMENT) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->writeAttribute($this-><?php echo $property->getName(); ?>::FIELD_VALUE, $this-><?php echo $property->getName(); ?>->_getFormattedValue());
            $xw->endElement();
        }
<?php   endif;
    elseif ($propTypeKind === TypeKindEnum::PRIMITIVE_CONTAINER) : ?>
<?php    if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ELEMENT) {
                    $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                    $v->xmlSerialize($xw, $config);
                    $xw->endElement();
                }
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getValueXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ELEMENT) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php   endif;
    elseif ($propTypeKind === TypeKindEnum::PHPFHIR_XHTML) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xr = $this-><?php echo $property->getName(); ?>->getXMLReader($config->getXHTMLLibxmlOpts());
            if (null !== $xr) {
                while ($xr->moveToNextAttribute()) {
                    $xw->writeAttribute($xr->name, $xr->value);
                }
                $xw->writeRaw($xr->readInnerXml());
            }
            $xw->endElement();
        }
<?php elseif ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach ($this-><?php echo $property->getName(); ?> as $v) {
                $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                $v->xmlSerialize($xw, $config);
                $xw->endElement();
            }
        }
<?php  else: ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php
    endif;

endforeach;

return ob_get_clean();
