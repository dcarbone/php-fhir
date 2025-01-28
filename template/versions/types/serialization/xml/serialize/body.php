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

$coreFiles = $version->getConfig()->getCoreFiles();

$xmlLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

ob_start();

// first, marshal attribute values
foreach ($type->getProperties()->getIterator() as $property) :
    if (!$property->isSerializableAsXMLAttribute()) {
        continue;
    }

    $propType = $property->getValueFHIRType();

    // if this is the "value" property on a primitive container, the value may be serialized to the parent's root node,
    // to the local node's attributes, or as an element on the local node.
    if ($type->isPrimitiveContainer() && $property->isValueProperty()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)
            && (<?php echo $xmlLocationEnum->getEntityName(); ?>::LOCAL_ATTRIBUTE === $valueLocation
                || (null === $valueLocation && $this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>] === <?php echo $xmlLocationEnum->getEntityName(); ?>::LOCAL_ATTRIBUTE))) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->_getFormattedValue());
        }
<?php
    // if this is a primitive property type, the value may only be placed either as an attribute or element of the
    // parent node.
    elseif ($propType->hasPrimitiveOrListParent() || $propType->isPrimitiveOrListType()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>] === <?php echo $xmlLocationEnum->getEntityName(); ?>::PARENT_ATTRIBUTE) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->_getFormattedValue());
        }
<?php
    // if this property is a value container, the value may be placed as an attribute on the parent node, an
    // attribute on the local node, or as an element of the local node.
    elseif ($propType->isValueContainer()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>] === <?php echo $xmlLocationEnum->getEntityName(); ?>::PARENT_ATTRIBUTE) {
            $xw->writeAttribute(self::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->getValue()?->_getFormattedValue());
        }
<?php
    endif;
endforeach;

// next, marshal parent attribute & element values
if ($type->hasConcreteParent()) : ?>
        parent::xmlSerialize($xw, $config<?php if ($type->hasPrimitiveContainerParent()) : ?>, $valueLocation<?php endif; ?>);
<?php endif;

// finally, marshal local element values
foreach ($type->getProperties()->getIterator() as $property) :
    $propType = $property->getValueFHIRType();
    $propTypeKind = $propType->getKind();

    // value property start
    if (!$property->isCollection() && $property->isValueProperty() && $type->isPrimitiveContainer()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>)
            && (<?php echo $xmlLocationEnum->getEntityName(); ?>::ELEMENT === $valueLocation
                || (null === $valueLocation
                    && $this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>] === <?php echo $xmlLocationEnum->getEntityName(); ?>::ELEMENT))) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->writeAttribute($this-><?php echo $property->getName(); ?>::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->_getFormattedValue());
            $xw->endElement();
        }
<?php
    // value property end

    // resource container start
    elseif ($propTypeKind->isResourceContainer($version)) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && [] !== $this-><?php echo $property->getName(); ?>) {
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
    // resource container end

    // primitive type start
    elseif ($propType->hasPrimitiveOrListParent() || $propType->isPrimitiveOrListType()) : ?>
<?php    if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && [] !== $this-><?php echo $property->getName(); ?>) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                $xw->writeAttribute($v::<?php echo $property->getFieldConstantName(); ?>, $v->_getFormattedValue());
                $xw->endElement();
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && $this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>] === <?php echo $xmlLocationEnum->getEntityName(); ?>::ELEMENT) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $xw->writeAttribute($this-><?php echo $property->getName(); ?>::<?php echo $property->getFieldConstantName(); ?>, $this-><?php echo $property->getName(); ?>->_getFormattedValue());
            $xw->endElement();
        }
<?php   endif;
    // primitive type end

    // primitive container start
    elseif ($propType->isPrimitiveContainer()) : ?>
<?php    if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && [] !== $this-><?php echo $property->getName(); ?>) {
            foreach($this-><?php echo $property->getName(); ?> as $v) {
                $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
                $v->xmlSerialize($xw, $config);
                $xw->endElement();
            }
        }
<?php   else : ?>
        if (isset($this-><?php echo $property->getName(); ?>)
            && ($this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>] === <?php echo $xmlLocationEnum->getEntityName(); ?>::ELEMENT
                || $this-><?php echo $property->getName(); ?>->_nonValueFieldDefined())) {
            $xw->startElement(self::<?php echo $property->getFieldConstantName(); ?>);
            $this-><?php echo $property->getName(); ?>->xmlSerialize($xw, $config, $this->_valueXMLLocations[self::<?php echo $property->getFieldConstantName(); ?>]);
            $xw->endElement();
        }
<?php   endif;
    // primitive container end

    // xhtml type start
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
<?php
    // xhtml type end

    // all other resource and element types start
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
<?php
    // all other resource and element types end
    endif;

endforeach;

return ob_get_clean();
