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

    $requiresXMLLocation = $propType->isValueContainer()
        || $propType->hasValueContainerParent()
        || $propType->isPrimitiveOrListType()
        || $propType->hasPrimitiveOrListParent()
        || $propType->isPrimitiveContainer()
        || $propType->hasPrimitiveContainerParent();

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
                $type-><?php echo $setter; ?>($value, <?php echo $valueXMLLocationEnum->getEntityName(); ?>::ELEMENT);
<?php   elseif ($propTypeKind->isResourceContainer($version)) : ?>
                foreach ($n->children() as $nn) {
                    /** @var <?php echo $containedTypeInterface->getFullyQualifiedName(true); ?> $cn */
                    $cn = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromXML($nn);
                    $type-><?php echo $setter; ?>($cn::xmlUnserialize($nn, null, $config));
                }
<?php   else :
            $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType); ?>
                $v = new <?php echo $propTypeClassname; ?>(<?php if ($requiresXMLLocation) : ?>valueXMLLocation: <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ELEMENT<?php endif; ?>);
                $type-><?php echo $setter; ?>(<?php echo $propTypeClassname; ?>::xmlUnserialize($n, $v, $config));
<?php   endif; ?>
            }<?php
endforeach; ?>

        }
        $attributes = $element->attributes();
<?php foreach ($type->getAllPropertiesIndexedIterator() as $i => $property) :
    $propConst = $property->getFieldConstantName();
    $propType = $property->getValueFHIRType();
    $setter = $property->getSetterName();

    $requiresXMLLocation = $propType === null
        || $propType->isValueContainer()
        || $propType->getKind()->isOneOf(TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE, TypeKindEnum::PRIMITIVE_CONTAINER)
        || $propType->hasPrimitiveOrListParent()
        || $propType->hasValueContainerParent()
        || $propType->hasPrimitiveContainerParent();

    if (null !== $propType) :
        $propTypeKind = $propType->getKind();

        if ($propType->hasPrimitiveOrListParent() || $propType->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE_CONTAINER)) :
            $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType); ?>
        if (isset($attributes[self::<?php echo $propConst; ?>])) {
<?php if ($property->isCollection()) :
                // TODO: this logic is a bit iffy, it will currently overwrite any existing values defined through elements on collection propreties.  Not sure what else to do about this.
                ?>
            $v = new <?php echo $propTypeClassname; ?>(value: (string)$attributes[self::<?php echo $propConst; ?>],
                                                       valueXMLLocation: <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ATTRIBUTE);
            $type-><?php echo $setter; ?>($v);
<?php else : ?>
            $pt = $type-><?php echo $property->getGetterName(); ?>();
            if (null !== $pt) {
                $pt->setValue(value:(string)$attributes[self::<?php echo $propConst; ?>]);
                $pt->_setValueXMLLocation(<?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ATTRIBUTE);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $propTypeClassname; ?>(
                    value: (string)$attributes[self::<?php echo $propConst; ?>],
                    valueXMLLocation: <?php echo PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION; ?>::ATTRIBUTE,
                ));
            }
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