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
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $localProperties */

ob_start();

// first, marshal attribute values

// this is only used in primitive types.  they have no other fields, and I am just going to assume you want it
// as an attribute if marshalled directly.
foreach ($type->getLocalProperties()->localPropertiesOfTypeKinds(includeCollections: false, kinds: null) as $property) : ?>
        $xw->writeAttribute(self::FIELD_VALUE, $this->getFormattedValue());
<?php endforeach;

foreach ($type->getLocalProperties()->localPropertiesIterator() as $property) :
    $pt = $property->getValueFHIRType();
    if (null === $pt) {
        continue;
    }
    if ($pt->hasPrimitiveParent() || $pt->getKind()->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) : ?>
        $locs = $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>] ?? [];
<?php   if ($property->isCollection()) : ?>
        if ([] === $locs && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $vs[0]->getFormattedValue());
        } else if (false !== ($idx = array_search(<?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE, $locs, true)) && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>()) && isset($vs[$idx])) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $vs[$idx]->getFormattedValue());
        }
<?php   else : ?>
        if (([] === $locs || (isset($locs[0]) && <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE === $locs[0])) && null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $v->getFormattedValue());
        }
<?php   endif;
    elseif ($pt->getKind() === TypeKind::PRIMITIVE_CONTAINER) : ?>
        $locs = $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>] ?? [];
<?php   if ($property->isCollection()) : ?>
        if ([] === $locs && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $vs[0]->getValue()?->getFormattedValue());
        } else if (false !== ($idx = array_search(<?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE, $locs, true)) && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>()) && isset($vs[$idx])) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $vs[$idx]->getValue()?->getFormattedValue());
        }
<?php   else: ?>
        if (([] === $locs || (isset($locs[0]) && <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE === $locs[0])) && null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $v->getValue()?->getFormattedValue());
        }
<?php   endif;
    endif;
endforeach;

// next, marshal parent attribute & element values
if ($type->hasParentWithLocalProperties()) : ?>
        parent::xmlSerialize($xw, $config);
<?php endif;

// finally, marshal local element values
foreach ($localProperties as $property) :
    $pt = $property->getValueFHIRType();

    // if this property has a "type"...
    if (null !== $pt) :
        $ptk = $pt->getKind();
        // ... and IS a containe
        if ($ptk->isContainer($config->getVersion()->getName())) :
            if ($property->isCollection()) : ?>
        foreach($this-><?php echo $property->getGetterName(); ?>() as $v) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->startElement($v->_getFhirTypeName());
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
            $xw->endElement();
        }
<?php       else : ?>
        if (null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->startElement($v->_getFhirTypeName());
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
            $xw->endElement();
        }
<?php       endif;
        elseif ($pt->hasPrimitiveParent() || $ptk->isOneOf(TypeKind::LIST, TypeKind::PRIMITIVE)) : ?>
        $locs = $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>] ?? [];
<?php        if ($property->isCollection()) : ?>
        if (([] === $locs || in_array(<?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT, $locs, true)) && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            foreach($vs as $i => $v) {
                if (!isset($locs[$i]) || <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT === $locs[$i]) {
                    $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                    $v->xmlSerialize($xw, $config);
                    $xw->endElement();
                }
            }
        }
<?php       else : ?>
        if (([] === $locs || (isset($locs[0]) && <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT === $locs[0])) && null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php       endif;
        elseif ($ptk === TypeKind::PRIMITIVE_CONTAINER) : ?>
        $locs = $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>] ?? [];
<?php        if ($property->isCollection()) : ?>
        if (([] === $locs || in_array(<?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT, $locs, true)) && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            foreach($vs as $i => $v) {
                if (!isset($locs[$i]) || <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT === $locs[$i]) {
                    $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                    $v->xmlSerialize($xw, $config);
                    $xw->endElement();
                }
            }
        }
<?php       else : ?>
        if (([] === $locs || (isset($locs[0]) && <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ELEMENT === $locs[0])) && null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php       endif; 
        elseif ($property->isCollection()) : ?>
        foreach ($this-><?php echo $property->getGetterName(); ?>() as $v) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $v->xmlSerialize($xw, $config);
            $xw->endElement();
        }
<?php  else: ?>
        if (null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $v->xmlSerialize($xw, $config);
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
