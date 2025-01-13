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

// this is only used in primitive types.  they have no other fields, and I am just going to assume you want it
// as an attribute if marshalled directly.
foreach ($type->getProperties()->getIteratorOfTypeKinds(includeCollections: false, kinds: null) as $property) : ?>
        $xw->writeAttribute(self::FIELD_VALUE, $this->getFormattedValue());
<?php endforeach;

foreach ($type->getProperties()->getIterator() as $property) :
    $pt = $property->getValueFHIRType();
    if (null === $pt) {
        continue;
    }
    if ($pt->hasPrimitiveParent() || $pt->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE) {
                    $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $v->getFormattedValue());
                    break;
                }
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->getFormattedValue());
        }
<?php   endif;
    elseif ($pt->getKind() === TypeKindEnum::PRIMITIVE_CONTAINER) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
           foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE) {
                    $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $v->getValue()?->getFormattedValue());
                    break;
                }
            }
        }
<?php   else: ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->getValue()?->getFormattedValue());
        }
<?php   endif;
    endif;
endforeach;

// next, marshal parent attribute & element values
if ($type->hasParentWithLocalProperties()) : ?>
        parent::xmlSerialize($xw, $config);
<?php endif;

// finally, marshal local element values
foreach ($type->getProperties()->getIterator() as $property) :
    $pt = $property->getValueFHIRType();

    // if this property has a "type"...
    if (null !== $pt) :
        $ptk = $pt->getKind();
        // ... and IS a container
        if ($ptk->isResourceContainer($version)) :
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
<?php       else : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->startElement($this-><?php echo $property->getName(); ?>->_getFHIRTypeName());
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config);
            $xw->endElement();
            $xw->endElement();
        }
<?php       endif;
        elseif ($pt->hasPrimitiveParent() || $ptk->isOneOf(TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE)) : ?>
<?php        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ELEMENT) {
                    $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                    $v->xmlSerialize($xw, $config);
                    $xw->endElement();
                }
            }
        }
<?php       else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ELEMENT) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php       endif;
        elseif ($ptk === TypeKindEnum::PRIMITIVE_CONTAINER) : ?>
<?php        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                if ($v->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ELEMENT) {
                    $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                    $v->xmlSerialize($xw, $config);
                    $xw->endElement();
                }
            }
        }
<?php       else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this-><?php echo $property->getName(); ?>->_getXMLLocation() === <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ELEMENT) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php       endif; 
        elseif ($property->isCollection()) : ?>
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
<?php   endif;

else:
    // NOTE: This clause is _only_ applicable to primitive types value's.  Since these are always assumed
    // to be attributes, this is useless.
    //
    // Uncomment and implement properly if the need arises in the future.
    /*?>
        if (($this->_xmlLocations[self::FIELD_VALUE] ?? <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE) === <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT) {
            $xw->writeSimpleElement(self::FIELD_VALUE, $this->getFormattedValue());
        }
<?php */ endif;

endforeach;

return ob_get_clean();
