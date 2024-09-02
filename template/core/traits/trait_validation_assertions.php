<?php declare(strict_types=1);

/*
 * Copyright 2022-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */

$rootNS = $config->getFullyQualifiedName(false);

ob_start();
echo "<?php declare(strict_types=1);\n\n";

if ('' !== $rootNS) :
    echo "namespace {$rootNS};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Trait <?php echo PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; if ('' !== $rootNS) : ?>

 * @package \<?php echo $rootNS; ?>
<?php endif; ?>

 */
trait <?php echo PHPFHIR_TRAIT_VALIDATION_ASSERTIONS; ?>

{
    /**
     * Asserts that a given collection field is of a specific minimum length
     * @param string $typeName
     * @param string $fieldName
     * @param int $expected
     * @param null|array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?> $value
     * @return null|string
     */
    protected function _assertMinOccurs(string $typeName, string $fieldName, int $expected, null|array|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $value): null|string
    {
        if (0 >= $expected || (1 === $expected && $value instanceof <?php echo PHPFHIR_INTERFACE_TYPE; ?>)) {
            return null;
        }
        if (null === $value || [] === $value) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, but it is empty', $fieldName, $typeName, $expected);
        }
        $len = count($value);
        if ($expected > $len) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, %d seen.', $fieldName, $typeName, $expected, $len);
        }
        return null;
    }

    /**
     * Asserts that a given collection field has no more than the specified number of elements
     * @param string $typeName
     * @param string $fieldName
     * @param int $expected
     * @param null|array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?> $value
     * @return null|string
     */
    protected function _assertMaxOccurs(string $typeName, string $fieldName, int $expected, null|array|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $value): null|string
    {
        if (<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::UNLIMITED === $expected || null === $value || [] === $value || $value instanceof <?php echo PHPFHIR_INTERFACE_TYPE; ?>) {
            return null;
        }
        $len = count($value);
        if ($expected >= $len) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must have no more than %d elements, %d seen', $fieldName, $typeName, $expected, $len);
    }

    /**
     * Asserts that a given string value is at least x characters long
     * @param string $typeName
     * @param string $fieldName
     * @param int $expected
     * @param null|string $value
     * @return null|string
     */
    protected function _assertMinLength(string $typeName, string $fieldName, int $expected, null|string $value): null|string
    {
        if (0 >= $expected) {
            return null;
        }
        if (null === $value || '' === $value) {
            return sprintf('Field "%s" on type "%s" must be at least %d characters long, but it is empty', $fieldName, $typeName, $expected);
        }
        $len = strlen($value);
        if ($expected <= $len) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must be at least %d characters long, %d seen.', $fieldName, $typeName, $expected, $len);
    }

    /**
     * Asserts that a given string value is no more than x characters long
     * @param string $typeName
     * @param string $fieldName
     * @param int $expected
     * @param null|string $value
     * @return null|string
     */
    protected function _assertMaxLength(string $typeName, string $fieldName, int $expected, null|string $value): null|string
    {
        if (<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::UNLIMITED === $expected || null === $value || '' === $value) {
            return null;
        }
        $len = strlen($value);
        if ($expected >= $len) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must be no more than %d characters long, %d seen', $fieldName, $typeName, $expected, $len);
    }

    /**
     * Asserts that a given value is within the expected list of values
     * @param string $typeName
     * @param string $fieldName
     * @param array $expected
     * @param mixed $value
     * @return null|string
     */
    protected function _assertValueInEnum(string $typeName, string $fieldName, array $expected, mixed $value): null|string
    {
        if ([] === $expected || in_array($value, $expected, true)) {
            return null;
        }
        return sprintf(
            'Field "%s" on type "%s" value "%s" not in allowed list: [%s]',
            $fieldName,
            $typeName,
            var_export($value, true),
            implode(
                ', ',
                array_map(
                    function($v) { return var_export($v, true); },
                    $expected
                )
            )
        );
    }

    /**
     * Asserts that a given string value matches the specified pattern
     * @param string $typeName
     * @param string $fieldName
     * @param string $pattern
     * @param null|string|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_PRIMITIVE_TYPE); ?> $value
     * @return null|string
     */
    protected function _assertPatternMatch(string $typeName, string $fieldName, string $pattern, null|string|<?php echo PHPFHIR_INTERFACE_PRIMITIVE_TYPE ?> $value): null|string
    {
        if ('' === $pattern || null === $value) {
            return null;
        }
        if ($value instanceof <?php echo PHPFHIR_INTERFACE_PRIMITIVE_TYPE; ?>) {
            $value = (string)$value;
        }
        if ('' === $value || (bool)preg_match($pattern, $value)) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" value of "%s" does not match pattern: %s', $fieldName, $typeName, $value, $pattern);
    }

    /**
     * @param string $typeName
     * @param string $fieldName
     * @param string $ruleName
     * @param mixed $constraint
     * @param mixed $value
     * @return null|string
     */
    protected function _performValidation(string $typeName, string $fieldName, string $ruleName, mixed $constraint, mixed $value): null|string
    {
        return match ($ruleName) {
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::VALIDATE_ENUM => $this->_assertValueInEnum($typeName, $fieldName, $constraint, $value),
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::VALIDATE_MIN_LENGTH => $this->_assertMinLength($typeName, $fieldName, $constraint, $value),
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::VALIDATE_MAX_LENGTH => $this->_assertMaxLength($typeName, $fieldName, $constraint, $value),
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::VALIDATE_MIN_OCCURS => $this->_assertMinOccurs($typeName, $fieldName, $constraint, $value),
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::VALIDATE_MAX_OCCURS => $this->_assertMaxOccurs($typeName, $fieldName, $constraint, $value),
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::VALIDATE_PATTERN => $this->_assertPatternMatch($typeName, $fieldName, $constraint, $value),
            default => sprintf('Type "%s" specifies unknown validation for field "%s": Name "%s"; Constraint "%s"', $typeName, $fieldName, $ruleName, var_export($constraint, true)),
        };
    }
}
<?php
return ob_get_clean();