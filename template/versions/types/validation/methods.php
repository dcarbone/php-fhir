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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$typeNameConst = $type->getTypeNameConst(true);
$typeKind = $type->getKind();

$requireArgs = [
    'version' => $version,
];

// TODO: this is a quick and lazy initial implementation.  Should improve this later...

ob_start(); ?>
    /**
     * Returns the validation rules that this type's fields must comply with to be considered "valid"
     * The returned array is in ["fieldname[.offset]" => ["rule" => {constraint}]]
     *
     * @return array
     */
    public function _getValidationRules(): array
    {
        return self::_DEFAULT_VALIDATION_RULES;
    }

    /**
     * Validates that this type conforms to the specifications set forth for it by FHIR.  An empty array must be seen as
     * passing.
     *
     * @return array
     */
    public function _getValidationErrors(): array
    {
<?php if ($type->hasParentWithLocalProperties()) : ?>
        $errs = parent::_getValidationErrors();
<?php else : ?>
        $errs = [];
<?php endif; ?>
        $validationRules = $this->_getValidationRules();
<?php foreach ($type->getProperties()->getLocalPropertiesIterator() as $property) :
    $validations = $property->buildValidationMap();
    if ([] === $validations) {
        continue;
    }
    $propertyType = $property->getValueFHIRType();
    if (null === $propertyType) :
        if ($property->isCollection()) : ?>
        if (isset($validationRules[self::FIELD_VALUE]) && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            foreach($vs as $i => $v) {
                $err = <?php echo PHPFHIR_CLASSNAME_VALIDATOR ?>::validateField(<?php echo $property->getMemberOf()->getTypeNameConst(true); ?>, self::<?php echo $property->getFieldConstantName(); ?>, $rule, $constraint, $v);
                if (null !== $err) {
                    $key = sprintf('%s.%d', self::<?php echo $property->getFieldConstantName(); ?>, $i);
                    if (!isset($errs[$key])) {
                        $errs[$key] = [];
                    }
                    $errs[$key][$rule] = $err;
                }
            }
        }
<?php
        else : ?>
        if (isset($validationRules[self::FIELD_VALUE]) && null !== $this->value) {
            foreach($validationRules[self::FIELD_VALUE] as $rule => $constraint) {
                $err = <?php echo PHPFHIR_CLASSNAME_VALIDATOR ?>::validateField(<?php echo $property->getMemberOf()->getTypeNameConst(true); ?>, self::<?php echo $property->getFieldConstantName(); ?>, $rule, $constraint, $this->getFormattedValue());
                if (null !== $err) {
                    if (!isset($errs[self::FIELD_VALUE])) {
                        $errs[self::FIELD_VALUE] = [];
                    }
                    $errs[self::FIELD_VALUE][$rule] = $err;
                }
            }
        }
<?php
        endif;
    elseif ($property->isCollection()) : ?>
        if ([] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            foreach($vs as $i => $v) {
                if ([] !== ($fieldErrs = $v->_getValidationErrors())) {
                    $errs[sprintf('%s.%d', self::<?php echo $property->getFieldConstantName(); ?>, $i)] = $fieldErrs;
                }
            }
        }
<?php
    else : ?>
        if (null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            if ([] !== ($fieldErrs = $v->_getValidationErrors())) {
                $errs[self::<?php echo $property->getFieldConstantName(); ?>] = $fieldErrs;
            }
        }
<?php
    endif;
endforeach;
if (null !== $type->getParentType()) :
    $ptype = $type;
    while (null !== $ptype) :
        foreach($ptype->getProperties()->getLocalPropertiesIterator() as $property) : ?>
        if (isset($validationRules[self::<?php echo $property->getFieldConstantName(); ?>])) {
            $v = $this-><?php echo $property->getGetterName(); ?>();
            foreach($validationRules[self::<?php echo $property->getFieldConstantName(); ?>] as $rule => $constraint) {
                $err = <?php echo PHPFHIR_CLASSNAME_VALIDATOR ?>::validateField(<?php echo $ptype->getTypeNameConst(true); ?>, self::<?php echo $property->getFieldConstantName(); ?>, $rule, $constraint, $v);
                if (null !== $err) {
                    if (!isset($errs[self::<?php echo $property->getFieldConstantName(); ?>])) {
                        $errs[self::<?php echo $property->getFieldConstantName(); ?>] = [];
                    }
                    $errs[self::<?php echo $property->getFieldConstantName(); ?>][$rule] = $err;
                }
            }
        }
<?php endforeach;
        $ptype = $ptype->getParentType();
    endwhile;
endif; ?>
        return $errs;
    }
<?php
return ob_get_clean();