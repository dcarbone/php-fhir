<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$pathResolver = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_PATH_RESOLVER);

$imports->addCoreFileImports(
    $typeInterface,
    $pathResolver,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Checks hydrated types against expected values, in the shape profile fixed / pattern values and slice matchers use:
 *
 * - a scalar is compared with the raw value of a primitive;
 * - an associative array is a pattern: every field named must hold a matching value;
 * - a list means every entry must be matched by some value of the field.
 *
 * Fields that hold collections match when any of their values matches.  Anything not mentioned is ignored, so
 * a pattern is a subset match.
 */
class <?php echo $coreFile; ?>

{
    /**
     * @param mixed $actual Primitive, complex type, or collection of either.
     * @param mixed $expected
     * @return bool
     */
    public static function matches(mixed $actual, mixed $expected): bool
    {
        if (is_array($expected) && [] !== $expected && array_is_list($expected)) {
            foreach ($expected as $one) {
                if (!self::_matchesAny($actual, $one)) {
                    return false;
                }
            }
            return true;
        }
        return self::_matchesAny($actual, $expected);
    }

    private static function _matchesAny(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            foreach ($actual as $item) {
                if (self::_matchesOne($item, $expected)) {
                    return true;
                }
            }
            return false;
        }
        return self::_matchesOne($actual, $expected);
    }

    private static function _matchesOne(mixed $actual, mixed $expected): bool
    {
        if (null === $actual) {
            return false;
        }
        if (!is_array($expected)) {
            return self::_scalarMatches(<?php echo $pathResolver; ?>::unwrap($actual), $expected);
        }
        if (!$actual instanceof <?php echo $typeInterface; ?>) {
            return false;
        }
        foreach ($expected as $field => $wanted) {
            if (!self::matches($actual->{'get' . $field}(), $wanted)) {
                return false;
            }
        }
        return true;
    }

    private static function _scalarMatches(mixed $raw, mixed $expected): bool
    {
        if (is_int($expected) || is_float($expected)) {
            $raw = self::_canonicalNumber($raw);
            return null !== $raw && $raw === self::_canonicalNumber($expected);
        }
        return $raw === $expected;
    }

    /**
     * Integer and decimal primitives hold their value as a string, so a numeric pattern value is compared as text with
     * insignificant zeros removed, never by casting to float, which would lose digits.  Exponent notation is not matched.
     *
     * @param mixed $number
     * @return null|string
     */
    private static function _canonicalNumber(mixed $number): null|string
    {
        if (is_int($number)) {
            return (string)$number;
        }
        if (is_float($number)) {
            $number = json_encode($number);
        }
        if (!is_string($number) || 1 !== preg_match('{^[+-]?\d+(\.\d+)?$}', $number)) {
            return null;
        }
        $number = ltrim($number, '+');
        if (str_contains($number, '.')) {
            $number = rtrim(rtrim($number, '0'), '.');
        }
        return '-0' === $number ? '0' : $number;
    }
}

<?php return ob_get_clean();
