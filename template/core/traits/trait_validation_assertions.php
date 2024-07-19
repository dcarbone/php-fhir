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

use DCarbone\PHPFHIR\Version\VersionCopyright;

/** @var \DCarbone\PHPFHIR\Config $config */

$rootNS = $config->getFullyQualifiedName(false);

ob_start();
echo "<?php declare(strict_types=1);\n\n";

if ('' !== $rootNS) :
    echo "namespace {$rootNS};\n\n";
endif;

echo VersionCopyright::getFullPHPFHIRCopyrightComment();

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
     * @param null|array $value)
     * @return null|string
     */
    protected function _assertMinOccurs(string $typeName, string $fieldName, int $expected, null|array $value): null|string
    {
        if (0 >= $expected) {
            return null;
        }
        if (!is_array($value) || [] === $value) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, but it is empty', $fieldName, $typeName, $expected);
        }
        if ($expected > ($cnt = count($value))) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, %d seen.', $fieldName, $typeName, $expected, $cnt);
        }
        return null;
    }

    /**
     * Asserts that a given collection field has no more than the specified number of elements
     * @param string $typeName
     * @param string $fieldName
     * @param int $expected
     * @param null|array $value
     * @return null|string
     */
    protected function _assertMaxOccurs(string $typeName, string $fieldName, int $expected, null|array $value): null|string
    {
        if (PHPFHIRConstants::UNLIMITED === $expected || null === $value || [] === $value || $expected >= ($cnt = count($value))) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must have no more than %d elements, %d seen', $fieldName, $typeName, $expected, $cnt);
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
        if (!is_string($value) || '' === $value) {
            return sprintf('Field "%s" on type "%s" must be at least %d characters long, but it is empty', $fieldName, $typeName, $expected);
        }
        $cnt = strlen($value);
        if ($expected <= $cnt) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must be at least %d characters long, %d seen.', $fieldName, $typeName, $expected, $cnt);
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
        if (PHPFHIRConstants::UNLIMITED === $expected || !is_string($value) || '' === $value) {
            return null;
        }
        $cnt = strlen($value);
        if ($expected >= $cnt) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must be no more than %d characters long, %d seen', $fieldName, $typeName, $expected, $cnt);
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
     * @param null|float|int|string|bool $value
     * @return null|string
     */
    protected function _assertPatternMatch(string $typeName, string $fieldName, string $pattern, null|float|int|string|bool $value): null|string
    {
        if ('' === $pattern || (bool)preg_match($pattern, (string)$value)) {
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
            PHPFHIRConstants::VALIDATE_ENUM => $this->_assertValueInEnum($typeName, $fieldName, $constraint, $value),
            PHPFHIRConstants::VALIDATE_MIN_LENGTH => $this->_assertMinLength($typeName, $fieldName, $constraint, $value),
            PHPFHIRConstants::VALIDATE_MAX_LENGTH => $this->_assertMaxLength($typeName, $fieldName, $constraint, $value),
            PHPFHIRConstants::VALIDATE_MIN_OCCURS => $this->_assertMinOccurs($typeName, $fieldName, $constraint, $value),
            PHPFHIRConstants::VALIDATE_MAX_OCCURS => $this->_assertMaxOccurs($typeName, $fieldName, $constraint, $value),
            PHPFHIRConstants::VALIDATE_PATTERN => $this->_assertPatternMatch($typeName, $fieldName, $constraint, $value),
            default => sprintf('Type "%s" specifies unknown validation for field "%s": Name "%s"; Constraint "%s"', $typeName, $fieldName, $ruleName, var_export($constraint, true)),
        };
    }
}
<?php
return ob_get_clean();