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

ob_start(); ?>
    /**
     * @return \stdClass
     */
    public function jsonSerialize(): mixed
    {
<?php if ($type->hasParentWithLocalProperties()) : ?>
        $out = parent::jsonSerialize();
<?php else : ?>
        $out = new \stdClass();
<?php endif;

if ($type->isCommentContainer() && !$type->hasCommentContainerParent()) : ?>
        if ([] !== ($vs = $this->_getFHIRComments())) {
            $out->{<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS} = $vs;
        }
<?php endif;
foreach ($type->getProperties()->getIterator() as $property) :
    $propConst = $property->getFieldConstantName();
    $propConstExt = $property->getFieldConstantExtensionName();
    $getter = $property->getGetterName();

    if ($property->getOverloadedProperty()) :
        continue;
    endif;
    $propertyType = $property->getValueFHIRType();
    if ($propertyType->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) :
        if ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            $out->{self::<?php echo $propConst; ?>} = $vs;
        }
<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
            $out->{self::<?php echo $propConst; ?>} = $v;
        }
<?php endif;

    elseif ($propertyType->isValueContainer() || $propertyType->getKind() === TypeKindEnum::PRIMITIVE_CONTAINER || $propertyType->hasPrimitiveContainerParent()) :
        $propTypeClassname = $property->getValueFHIRType()->getClassName();

        if ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            $vals = [];
            $exts = [];
            foreach ($vs as $v) {
                if (null === $v) {
                    continue;
                }
                $val = $v->getValue();
                $ext = $v->jsonSerialize();
                unset($ext->{<?php echo $propTypeClassname; ?>::FIELD_VALUE});
                if (null !== $val) {
                    $vals[] = $val;
                }
                if ([] !== $ext) {
                    $exts[] = $ext;
                }
            }
            if ([] !== $vals) {
                $out->{self::<?php echo $propConst; ?>} = $vals;
            }
            if (count((array)$ext) > 0) {
                $out->{self::<?php echo $propConstExt; ?>} = $exts;
            }
        }
<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
            if (null !== ($val = $v->getValue())) {
                $out->{self::<?php echo $propConst; ?>} = $val;
            }
            $ext = $v->jsonSerialize();
            unset($ext->{<?php echo $propTypeClassname; ?>::FIELD_VALUE});
            if (count((array)$ext) > 0) {
                $out->{self::<?php echo $propConstExt; ?>} = $ext;
            }
        }
<?php endif;

    else :
        if ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            $out->{self::<?php echo $propConst; ?>} = [];
            foreach($vs as $v) {
                $out->{self::<?php echo $propConst; ?>}[] = $v;
            }
        }
<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
            $out->{self::<?php echo $propConst; ?>} = $v;
        }
<?php endif;
    endif;
endforeach;
if ($type->isCommentContainer() && !$type->hasCommentContainerParent()) : ?>
        if ([] !== ($vs = $this->_getFHIRComments())) {
            $out->{<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS} = $vs;
        }
<?php endif; ?>

<?php if ($type->isContainedType()) : ?>
        $out->{<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE} = $this->_getResourceType();

<?php endif; ?>
        return $out;
    }
<?php return ob_get_clean();
