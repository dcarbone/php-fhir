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

    $propName = $property->getName();
    $propConst = $property->getFieldConstantName();
    $propConstExt = $property->getFieldConstantExtensionName();

    $propertyType = $property->getValueFHIRType();
    if ($propertyType->isPrimitiveType() || $propertyType->hasPrimitiveTypeParent()) :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $propName; ?>) && [] !== $this-><?php echo $propName; ?>) {
            if ($this->_getJSONFieldElideSingletonArray(self::<?php echo $propConst; ?>) && 1 === count($this-><?php echo $propName; ?>)) {
                $out-><?php echo $propName; ?> = $this-><?php echo $propName; ?>[0];
            } else {
                $out-><?php echo $propName; ?> = $this-><?php echo $propName; ?>;
            }
        }
<?php else : ?>
        if (isset($this-><?php echo $propName; ?>)) {
            $out-><?php echo $propName; ?> = $this-><?php echo $propName; ?>;
        }
<?php endif;

    elseif ($propertyType->isPrimitiveContainer() || $propertyType->hasPrimitiveContainerParent()) :
        $propTypeClassname = $property->getValueFHIRType()->getClassName();

        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $propName; ?>) && [] !== $this-><?php echo $propName; ?>) {
            $vals = [];
            $exts = [];
            $hasVals = false;
            $hasExts = false;
            foreach ($this-><?php echo $propName; ?> as $v) {
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
                $out-><?php echo $propName; ?> = $vals;
            }
            if ($hasExts) {
                $out-><?php echo $property->getExtName(); ?> = $exts;
            }
        }
<?php else : ?>
        if (isset($this-><?php echo $propName; ?>)) {
            if (null !== ($val = $this-><?php echo $propName; ?>->getValue())) {
                $out-><?php echo $propName; ?> = $val;
            }
            if ($this-><?php echo $propName; ?>->_nonValueFieldDefined()) {
                $ext = $this-><?php echo $propName; ?>->jsonSerialize();
                unset($ext->value);
                $out-><?php echo $property->getExtName(); ?> = $ext;
            }
        }
<?php endif;

    else :
        if ($property->isCollection()) : ?>
        if (isset($this-><?php echo $propName; ?>) && [] !== $this-><?php echo $propName; ?>) {
            if ($this->_getJSONFieldElideSingletonArray(self::<?php echo $propConst; ?>) && 1 === count($this-><?php echo $propName; ?>)) {
                $out-><?php echo $propName; ?> = $this-><?php echo $propName; ?>[0];
            } else {
                $out-><?php echo $propName; ?> = $this-><?php echo $propName; ?>;
            }
        }
<?php else : ?>
        if (isset($this-><?php echo $propName; ?>)) {
            $out-><?php echo $propName; ?> = $this-><?php echo $propName; ?>;
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
