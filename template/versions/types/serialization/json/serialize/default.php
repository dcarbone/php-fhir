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
            $out->fhir_comments = $vs;
        }
<?php endif;
foreach ($type->getProperties()->getIterator() as $property) :
    if ($property->getOverloadedProperty()) {
        continue;
    }

    $propConst = $property->getFieldConstantName();
    $propConstExt = $property->getFieldConstantExtensionName();

    $propertyType = $property->getValueFHIRType();
    if ($propertyType->isPrimitiveOrListType() || $propertyType->hasPrimitiveOrListParent()) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && [] !== $this-><?php echo $property->getName(); ?>) {
            $out-><?php echo $property->getName(); ?> = $this-><?php echo $property->getName(); ?>;
        }
<?php else : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            $out-><?php echo $property->getName(); ?> = $this-><?php echo $property->getName(); ?>;
        }
<?php endif;

    elseif ($propertyType->isPrimitiveContainer() || $propertyType->hasPrimitiveContainerParent()) :
        $propTypeClassname = $property->getValueFHIRType()->getClassName();

        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && [] !== $this-><?php echo $property->getName(); ?>) {
            $vals = [];
            $exts = [];
            $hasVals = false;
            $hasExts = false;
            foreach ($this-><?php echo $property->getName(); ?> as $v) {
                $val = $v->getValue();
                if (null !== $val) {
                    $hasVals = true;
                    $vals[] = $val;
                } else {
                    $vals[] = null;
                }
                if ($v->_nonValueFieldDefined()) {
                    $hasExts = true;
                    $ext = $v->jsonSerialize();
                    unset($ext->value);
                    $exts[] = $ext;
                } else {
                    $exts[] = null;
                }
            }
            if ($hasVals) {
                $out-><?php echo $property->getName(); ?> = $vals;
            }
            if ($hasExts) {
                $out-><?php echo $property->getExtName(); ?> = $exts;
            }
        }
<?php else : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            if (null !== ($val = $this-><?php echo $property->getName(); ?>->getValue())) {
                $out-><?php echo $property->getName(); ?> = $val;
            }
            if ($this-><?php echo $property->getName(); ?>->_nonValueFieldDefined()) {
                $ext = $this-><?php echo $property->getName(); ?>->jsonSerialize();
                unset($ext->value);
                $out-><?php echo $property->getExtName(); ?> = $ext;
            }
        }
<?php endif;

    else :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $property->getName(); ?>) && [] !== $this-><?php echo $property->getName(); ?>) {
            $out-><?php echo $property->getName(); ?> = $this-><?php echo $property->getName(); ?>;
        }
<?php else : ?>
        if (isset($this-><?php echo $property->getName(); ?>)) {
            $out-><?php echo $property->getName(); ?> = $this-><?php echo $property->getName(); ?>;
        }
<?php endif;
    endif;
endforeach;
if ($type->isCommentContainer() && !$type->hasCommentContainerParent()) : ?>
        if ([] !== ($vs = $this->_getFHIRComments())) {
            $out->fhir_comments = $vs;
        }
<?php endif;
    if ($type->isContainedType()) : ?>
        $out->resourceType = $this->_getResourceType();
<?php endif; ?>
        return $out;
    }
<?php return ob_get_clean();
