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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

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
        return self::_VALIDATION_RULES;
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
<?php if ($type->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) : ?>
        $validationRules = $this->_getValidationRules();
<?php endif; ?>
<?php foreach ($type->getLocalProperties()->getLocalPropertiesIterator() as $property) {
    $propertyType = $property->getValueFHIRType();
    if (null === $propertyType) {
        if ($property->isCollection()) {
            echo require_with(
                PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . 'collection_typed.php',
                $requireArgs + ['property' => $property]
            );
        } else {
            echo require_with(
                PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . 'primitive.php',
                $requireArgs + ['property' => $property]
            );
        }
    } else if ($propertyType->getKind() === TypeKindEnum::PHPFHIR_XHTML) {
        // TODO(@dcarbone): better way to omit validation
        continue;
    } else if ($property->isCollection()) {
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . 'collection_typed.php',
            $requireArgs + ['property' => $property]
        );
    } else {
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR . DIRECTORY_SEPARATOR . 'methods' . DIRECTORY_SEPARATOR . 'typed.php',
            $requireArgs + ['property' => $property]
        );
    }
}
if (null !== $type->getParentType()) :
    $ptype = $type;
    while (null !== $ptype) :
        foreach($ptype->getLocalProperties()->getLocalPropertiesIterator() as $property) : ?>
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