<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$constantsClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CONSTANTS);
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);

$imports->addCoreFileImports(
    $constantsClass,
    $typeInterface,
    $primitiveTypeInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR; ?>

{
    /**
     * Asserts that a given collection field is of a specific minimum length
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param int $expected
     * @param null|array|<?php echo $primitiveTypeInterface->getFullyQualifiedName(true); ?> $value
     * @return null|string
     */
    public static function assertMinOccurs(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, int $expected, null|array|<?php echo $primitiveTypeInterface->getEntityName(); ?> $value): null|string
    {
        if (0 >= $expected || (1 === $expected && $value instanceof <?php echo $typeInterface->getEntityName(); ?>)) {
            return null;
        }
        if (null === $value || [] === $value) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, but it is empty', $fieldName, $type->_getFHIRTypeName(), $expected);
        }
        $len = count($value);
        if ($expected > $len) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, %d seen.', $fieldName, $type->_getFHIRTypeName(), $expected, $len);
        }
        return null;
    }

    /**
     * Asserts that a given collection field has no more than the specified number of elements
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param int $expected
     * @param null|array|<?php echo $typeInterface->getFullyQualifiedName(true); ?> $value
     * @return null|string
     */
    public static function assertMaxOccurs(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, int $expected, null|array|<?php echo $typeInterface->getEntityName(); ?> $value): null|string
    {
        if (<?php echo $constantsClass->getEntityName(); ?>::UNLIMITED === $expected || null === $value || [] === $value || $value instanceof <?php echo $typeInterface->getEntityName(); ?>) {
            return null;
        }
        $len = count($value);
        if ($expected >= $len) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must have no more than %d elements, %d seen', $fieldName, $type->_getFHIRTypeName(), $expected, $len);
    }

    /**
     * Asserts that a given string value is at least x characters long
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param int $expected
     * @param null|string $value
     * @return null|string
     */
    public static function assertMinLength(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, int $expected, null|string $value): null|string
    {
        if (0 >= $expected) {
            return null;
        }
        if (null === $value || '' === $value) {
            return sprintf('Field "%s" on type "%s" must be at least %d characters long, but it is empty', $fieldName, $type->_getFHIRTypeName(), $expected);
        }
        $len = strlen($value);
        if ($expected <= $len) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must be at least %d characters long, %d seen.', $fieldName, $type->_getFHIRTypeName(), $expected, $len);
    }

    /**
     * Asserts that a given string value is no more than x characters long
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param int $expected
     * @param null|string $value
     * @return null|string
     */
    public static function assertMaxLength(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, int $expected, null|string $value): null|string
    {
        if (<?php echo $constantsClass->getEntityName(); ?>::UNLIMITED === $expected || null === $value || '' === $value) {
            return null;
        }
        $len = strlen($value);
        if ($expected >= $len) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" must be no more than %d characters long, %d seen', $fieldName, $type->_getFHIRTypeName(), $expected, $len);
    }

    /**
     * Asserts that a given value is within the expected list of values
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param array $expected
     * @param mixed $value
     * @return null|string
     */
    public static function assertValueInEnum(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, array $expected, mixed $value): null|string
    {
        if ([] === $expected || in_array($value, $expected, true)) {
            return null;
        }
        return sprintf(
            'Field "%s" on type "%s" value "%s" not in allowed list: [%s]',
            $fieldName,
            $type->_getFHIRTypeName(),
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
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param string $pattern
     * @param null|string|<?php echo $primitiveTypeInterface->getFullyQualifiedName(true); ?> $value
     * @return null|string
     */
    public static function assertPatternMatch(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, string $pattern, null|string|<?php echo PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE ?> $value): null|string
    {
        if ('' === $pattern || null === $value) {
            return null;
        }
        if ($value instanceof <?php echo $primitiveTypeInterface->getEntityName(); ?>) {
            $value = (string)$value;
        }
        if ('' === $value || (bool)preg_match($pattern, $value)) {
            return null;
        }
        return sprintf('Field "%s" on type "%s" value of "%s" does not match pattern: %s', $fieldName, $type->_getFHIRTypeName(), $value, $pattern);
    }

    /**
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $fieldName
     * @param string $rule
     * @param mixed $constraint
     * @param mixed $value
     * @return null|string
     */
    public static function validateField(<?php echo $typeInterface->getEntityName(); ?> $type, string $fieldName, string $rule, mixed $constraint, mixed $value): null|string
    {
        if (null === $constraint) {
            return null;
        }
        return match ($rule) {
            <?php echo $constantsClass->getEntityName(); ?>::VALIDATE_ENUM => static::assertValueInEnum($type, $fieldName, $constraint, $value),
            <?php echo $constantsClass->getEntityName(); ?>::VALIDATE_MIN_LENGTH => static::assertMinLength($type, $fieldName, $constraint, $value),
            <?php echo $constantsClass->getEntityName(); ?>::VALIDATE_MAX_LENGTH => static::assertMaxLength($type, $fieldName, $constraint, $value),
            <?php echo $constantsClass->getEntityName(); ?>::VALIDATE_MIN_OCCURS => static::assertMinOccurs($type, $fieldName, $constraint, $value),
            <?php echo $constantsClass->getEntityName(); ?>::VALIDATE_MAX_OCCURS => static::assertMaxOccurs($type, $fieldName, $constraint, $value),
            <?php echo $constantsClass->getEntityName(); ?>::VALIDATE_PATTERN => static::assertPatternMatch($type, $fieldName, $constraint, $value),
            default => sprintf('Type "%s" specifies unknown validation for field "%s": Name "%s"; Constraint "%s"', $type, $fieldName, $rule, var_export($constraint, true)),
        };
    }
}
<?php return ob_get_clean();
