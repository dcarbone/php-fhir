<?php

/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$typeNameConst = $type->getTypeNameConst(true);
$typeKind = $type->getKind();

// TODO: this is a quick and lazy initial implementation.  Should improve this later...

ob_start(); ?>
    /**
     * Returns the validation rules that this type's fields must comply with to be considered "valid"
     * The returned array is in ["fieldname[.offset]" => ["rule" => {constraint}]]
     *
     * @return array
     */
    public function _getValidationRules()
    {
        return self::$_validationRules;
    }

    /**
     * Validates that this type conforms to the specifications set forth for it by FHIR.  An empty array must be seen as
     * passing.
     *
     * @return array
     */
    public function _getValidationErrors()
    {
<?php if (null !== $type->getParentType()) : ?>
        $errs = parent::_getValidationErrors();
<?php else : ?>
        $errs = [];
<?php endif; ?>
        $validationRules = $this->_getValidationRules();
<?php foreach ($type->getProperties()->getDirectIterator() as $property) :
    $propertyType = $property->getValueFHIRType();
    if (null === $propertyType) :
        if ($property->isCollection()) :
            echo require_with(
                PHPFHIR_TEMPLATE_VALIDATION_DIR . '/methods/collection_typed.php',
                [
                    'property' => $property
                ]
            );
        else :
            echo require_with(
                PHPFHIR_TEMPLATE_VALIDATION_DIR . '/methods/primitive.php',
                [
                    'property' => $property
                ]
            );
        endif;
    else :
        if ($property->isCollection()) :
            echo require_with(
                PHPFHIR_TEMPLATE_VALIDATION_DIR . '/methods/collection_typed.php',
                [
                        'property' => $property
                ]
            );
        else :
            echo require_with(
                    PHPFHIR_TEMPLATE_VALIDATION_DIR . '/methods/typed.php',
                [
                    'property' => $property
                ]
            );
        endif;
    endif;
endforeach;
if (null !== $type->getParentType()) :
    $ptype = $type;
    while (null !== $ptype) :
        foreach($ptype->getProperties()->getDirectIterator() as $property) : ?>
        if (isset($validationRules[self::<?php echo $property->getFieldConstantName(); ?>])) {
            $v = $this-><?php echo $property->getGetterName(); ?>();
            foreach($validationRules[self::<?php echo $property->getFieldConstantName(); ?>] as $rule => $constraint) {
                $err = $this->_performValidation(<?php echo $ptype->getTypeNameConst(true); ?>, self::<?php echo $property->getFieldConstantName(); ?>, $rule, $constraint, $v);
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