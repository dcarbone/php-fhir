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
use DCarbone\PHPFHIR\Enum\XMLValueLocationUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$coreFiles = $version->getConfig()->getCoreFiles();

$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

$containedTypeInterface = $version->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);

ob_start(); ?>
        foreach ($element->children() as $n) {
            $childName = $n->getName();
<?php foreach ($type->getAllPropertiesIndexedIterator() as $i => $property) :
    $propType = $property->getValueFHIRType();
    $propTypeKind = $propType->getKind();
    $setter = $property->getSetterName();
    $propConst = $property->getFieldConstantName();

    if ($i > 0) : ?> else <?php else : ?>            <?php endif; ?>if (self::<?php echo $propConst; ?> === $childName) {
<?php   if ($propType->isPrimitiveOrListType()) : ?>
                $valueAttr = $n->attributes()[<?php echo $propType->getClassName(); ?>::FIELD_VALUE] ?? null;
                if (null !== $valueAttr) {
                    $value = (string)$valueAttr;
                } else if ($n->hasChildren()) {
                    $value = $n->saveXML();
                } else {
                    $value = (string)$n;
                }
                $type-><?php echo $setter; ?>($value, valueXMLLocation: <?php echo $valueXMLLocationEnum->getEntityName(); ?>::ELEMENT);
<?php   elseif ($propTypeKind->isResourceContainer($version)) : ?>
                foreach ($n->children() as $nn) {
                    /** @var <?php echo $containedTypeInterface->getFullyQualifiedName(true); ?> $cn */
                    $cn = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromXML($nn);
                    $type-><?php echo $setter; ?>($cn::xmlUnserialize($nn, $config));
                }
<?php   elseif ($propTypeKind === TypeKindEnum::PHPFHIR_XHTML) : ?>
                $type-><?php echo $setter; ?>($n);
<?php   else :
            $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType); ?>
                $type-><?php echo $setter; ?>(<?php echo $propTypeClassname; ?>::xmlUnserialize($n, $config));
<?php   endif; ?>
            }<?php
endforeach; ?>

        }
        $attributes = $element->attributes();
<?php
// start attribute parsing
foreach ($type->getAllPropertiesIndexedIterator() as $i => $property) :
    if (!$property->isSerializableAsXMLAttribute()) {
        continue;
    }

    $propConst = $property->getFieldConstantName();
    $propType = $property->getValueFHIRType();
    $setter = $property->getSetterName();
?>
        if (isset($attributes[self::<?php echo $propConst; ?>])) {
<?php
    // primitive properties need to be set directly on the type
    if (null === $propType) : ?>
            $type-><?php echo $setter; ?>((string)$attributes[self::<?php echo $propConst; ?>], <?php echo $valueXMLLocationEnum->getEntityName(); ?>::LOCAL_ATTRIBUTE);
<?php
    // "value container" type properties can have their "value" property defined either on their parent's node as an
    // attribute, on their own node as an attribute, or as a child of themselves.
    else : ?>
            if (isset($type-><?php echo $property->getName(); ?>)) {
                $type-><?php echo $property->getName(); ?>->setValue((string)$attributes[self::<?php echo $propConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>((string)$attributes[self::<?php echo $propConst; ?>]);
            }
<?php
    endif;
    // if the local type is a "value container", this indicates the value was found on the container's local attributes
    if ($type->isValueContainer() || $type->hasValueContainerParent()) : ?>
            $type->_set<?php echo ucfirst($property->getName()); ?>ValueXMLLocation(<?php echo $valueXMLLocationEnum->getEntityName(); ?>::LOCAL_ATTRIBUTE);
<?php
    else :
    // if the local type is NOT a "value container", this indicates the property's value was found in a PARENT node's
    // attributes.
        ?>
            $type->_set<?php echo ucfirst($property->getName()); ?>ValueXMLLocation(<?php echo $valueXMLLocationEnum->getEntityName(); ?>::PARENT_ATTRIBUTE);
<?php
    endif;
?>
        }
<?php

// end attribute parsing
endforeach;

return ob_get_clean();