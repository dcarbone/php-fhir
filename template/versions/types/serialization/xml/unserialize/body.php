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
                $type-><?php echo $setter; ?>(<?php echo $property->getName(); ?>: $value, valueXMLLocation: <?php echo $valueXMLLocationEnum->getEntityName(); ?>::ELEMENT);
<?php   elseif ($propTypeKind->isResourceContainer($version)) : ?>
                foreach ($n->children() as $nn) {
                    /** @var <?php echo $containedTypeInterface->getFullyQualifiedName(true); ?> $cn */
                    $cn = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromXML($nn);
                    $type-><?php echo $setter; ?>(<?php echo $property->getName(); ?>: $cn::xmlUnserialize($nn, null, $config));
                }
<?php   elseif ($propTypeKind === TypeKindEnum::PHPFHIR_XHTML) : ?>
                $type-><?php echo $setter; ?>($n);
<?php   else :
            $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType); ?>
                $type-><?php echo $setter; ?>(<?php echo $property->getName(); ?>: <?php echo $propTypeClassname; ?>::xmlUnserialize($n, null, $config)<?php if ($property->requiresXMLLocation()) : ?>, valueXMLLocation: <?php echo $valueXMLLocationEnum->getEntityName(); ?>::ELEMENT<?php endif; ?>);
<?php   endif; ?>
            }<?php
endforeach; ?>

        }
        $attributes = $element->attributes();
<?php foreach ($type->getAllPropertiesIndexedIterator() as $i => $property) :
    $propConst = $property->getFieldConstantName();
    $propType = $property->getValueFHIRType();
    $setter = $property->getSetterName();

    if (null !== $propType) :
        $propTypeKind = $propType->getKind();

        if ($propType->hasPrimitiveOrListParent() || $propType->isPrimitiveOrListType() || $propType->isPrimitiveContainer()) :
            $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType); ?>
        if (isset($attributes[self::<?php echo $propConst; ?>])) {
<?php if ($property->isCollection()) : ?>
            $type-><?php echo $setter; ?>(<?php echo $property->getName(); ?>: (string)$attributes[self::<?php echo $propConst; ?>]);
<?php else : ?>
            $pt = $type-><?php echo $property->getGetterName(); ?>();
            if (null !== $pt) {
                $pt->setValue(value: (string)$attributes[self::<?php echo $propConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(<?php echo $property->getName(); ?>: (string)$attributes[self::<?php echo $propConst; ?>]);
            }
<?php if ($property->requiresXMLLocation()) : ?>
            $type->_set<?php echo ucfirst($property->getName()); ?>ValueXMLLocation(<?php echo $valueXMLLocationEnum->getEntityName(); ?>::ATTRIBUTE);
<?php endif; ?>
<?php endif; ?>
        }
<?php endif;
    else : ?>
        if (isset($attributes[self::<?php echo $property->getFieldConstantName(); ?>])) {
            $type->setValue((string)$attributes[self::<?php echo $property->getFieldConstantName(); ?>]);
        }
<?php
    endif;
endforeach;
return ob_get_clean();