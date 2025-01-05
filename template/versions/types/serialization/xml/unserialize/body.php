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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

ob_start(); ?>
        foreach ($element->children() as $n) {
            $childName = $n->getName();
<?php foreach ($type->getAllPropertiesIndexedIterator() as $i => $property) :
    $propConst = $property->getFieldConstantName();
    $propType = $property->getValueFHIRType();
    $setter = $property->getSetterName();

    if ($i > 0) : ?> else<?php else : ?>            <?php endif;     
    if (null !== $propType) :
        $propTypeKind = $propType->getKind();
        $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType);

        if ($propTypeKind->isContainer($version)) : ?>
            if (self::<?php echo $propConst; ?> === $childName) {
                foreach ($n->children() as $nn) {
                    $typeClassName = <?php echo PHPFHIR_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromXML($nn);
                    $type-><?php echo $setter; ?>($typeClassName::xmlUnserialize($nn, null, $config));
                }
            }<?php
        else : ?>if (self::<?php echo $propConst; ?> === $childName) {
                $type-><?php echo $setter; ?>(<?php echo $propTypeClassname; ?>::xmlUnserialize($n, null, $config)<?php if ($propType->hasPrimitiveParent() || $propType->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE_CONTAINER)) : ?>, <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ELEMENT<?php endif; ?>);
            }<?php
        endif;
    else : ?>if (self::<?php echo $propConst; ?> === $childName) {
                $valueAttr = $n->attributes()[self::FIELD_VALUE] ?? null;
                if (null !== $valueAttr) {
                    $type->setValue((string)$valueAttr);
                } elseif ($n->hasChildren()) {
                    $type->setValue($n->saveXML());
                } else {
                    $type->setValue((string)$n);
                }
            }<?php
    endif;
endforeach; ?>

        }
        $attributes = $element->attributes();
<?php foreach ($type->getAllPropertiesIndexedIterator() as $i => $property) :
    $propConst = $property->getFieldConstantName();
    $propType = $property->getValueFHIRType();
    $setter = $property->getSetterName();

    if (null !== $propType) :
        $propTypeKind = $propType->getKind();
        $propTypeClassname = $property->getMemberOf()->getImports()->getImportByType($propType);

        if ($propType->hasPrimitiveParent() || $propType->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE_CONTAINER)) : ?>
        if (isset($attributes[self::<?php echo $propConst; ?>])) {
<?php if ($property->isCollection()) : ?>
            $type-><?php echo $setter; ?>((string)$attributes[self::<?php echo $propConst; ?>], <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE);
<?php else : ?>
            $pt = $type-><?php echo $property->getGetterName(); ?>();
            if (null !== $pt) {
                $pt->setValue((string)$attributes[self::<?php echo $propConst; ?>], <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE);
            } else {
                $type-><?php echo $setter; ?>((string)$attributes[self::<?php echo $propConst; ?>], <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE);
            }
<?php endif; ?>
        }
<?php endif;
    else : ?>
        if (isset($attributes[self::<?php echo $property->getFieldConstantName(); ?>])) {
            $type->setValue((string)$attributes[self::<?php echo $property->getFieldConstantName(); ?>], <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ATTRIBUTE);
        }
<?php
    endif;
endforeach;
return ob_get_clean();