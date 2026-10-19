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
 * Checks hydrated types against expected values, in the shape profile fixed / pattern values and slice matchers use.
 *
 * matches() is a subset match, used for patterns and slice discrimination:
 *
 * - a scalar is compared with the raw value of a primitive;
 * - an associative array is a pattern: every field named must hold a matching value;
 * - a list means every entry must be matched by some value of the field.
 *
 * Fields that hold collections match when any of their values matches.  Anything not mentioned is ignored.
 *
 * matchesEach() and equals() check every occurrence of a repeating element on its own, as a pattern or a fixed value
 * must hold for each of them.  equals() is an exact match: a complex value must have the same fields and the same
 * number of entries, in order, as the fixed value.  A primitive is compared on its value only, its id and extensions
 * not being part of a fixed value.
 */
class <?php echo $coreFile; ?>

{
    // sign, whole digits, fraction digits, exponent.
    private const _NUMBER_PATTERN = '{^([+-]?)(\d+)(?:\.(\d+))?(?:[eE]([+-]?\d+))?$}';
    // well beyond any float, and keeps the expanded text of an exponent bounded.
    private const _MAX_EXPONENT = 1000;

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

    /**
     * @param mixed $actual Primitive, complex type, or collection of either.
     * @param mixed $expected
     * @return bool True if every value, or every value of the collection, matches the pattern.
     */
    public static function matchesEach(mixed $actual, mixed $expected): bool
    {
        if (!is_array($actual)) {
            return self::matches($actual, $expected);
        }
        foreach ($actual as $item) {
            if (!self::matches($item, $expected)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $actual Primitive, complex type, or collection of either.
     * @param mixed $expected
     * @return bool True if every value, or every value of the collection, is exactly the fixed value.
     */
    public static function equals(mixed $actual, mixed $expected): bool
    {
        if (!is_array($actual)) {
            return self::_equalsOne($actual, $expected);
        }
        foreach ($actual as $item) {
            if (!self::_equalsOne($item, $expected)) {
                return false;
            }
        }
        return true;
    }

    private static function _equalsOne(mixed $actual, mixed $expected): bool
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
        return self::_jsonEquals(json_decode(json_encode($actual), true), $expected);
    }

    /**
     * @param mixed $actual JSON form of the instance value, decoded to arrays.
     * @param mixed $expected JSON form of the fixed value.
     * @return bool
     */
    private static function _jsonEquals(mixed $actual, mixed $expected): bool
    {
        if (!is_array($expected)) {
            return !is_array($actual) && self::_scalarMatches($actual, $expected);
        }
        if (!is_array($actual)) {
            return false;
        }
        if ([] === $expected || array_is_list($expected)) {
            // a single entry may be written without its array.
            if ([] !== $actual && !array_is_list($actual)) {
                $actual = [$actual];
            }
            if (count($actual) !== count($expected)) {
                return false;
            }
            foreach ($expected as $i => $one) {
                if (!self::_jsonEquals($actual[$i], $one)) {
                    return false;
                }
            }
            return true;
        }
        unset($actual['fhir_comments']);
        if (count($actual) !== count($expected)) {
            return false;
        }
        foreach ($expected as $field => $wanted) {
            if (!array_key_exists($field, $actual) || !self::_jsonEquals($actual[$field], $wanted)) {
                return false;
            }
        }
        return true;
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
     * Integer and decimal primitives hold their value as a string, so a numeric value is compared as plain decimal text
     * with the exponent applied and insignificant zeros removed, never by casting to float, which would lose digits.
     *
     * @param mixed $number
     * @return null|string Null if the value is not a finite number, or its exponent is out of range.
     */
    private static function _canonicalNumber(mixed $number): null|string
    {
        if (is_int($number)) {
            return (string)$number;
        }
        if (is_float($number)) {
            if (!is_finite($number)) {
                return null;
            }
            $number = self::_shortestFloat($number);
        }
        if (!is_string($number) || 1 !== preg_match(self::_NUMBER_PATTERN, $number, $m)) {
            return null;
        }
        $exponent = (int)($m[4] ?? 0);
        if (abs($exponent) > self::_MAX_EXPONENT) {
            return null;
        }
        $digits = $m[2] . ($m[3] ?? '');
        $point = strlen($m[2]) + $exponent;
        if ($point < 1) {
            $digits = str_repeat('0', 1 - $point) . $digits;
            $point = 1;
        } else if ($point > strlen($digits)) {
            $digits .= str_repeat('0', $point - strlen($digits));
        }
        $whole = ltrim(substr($digits, 0, $point), '0');
        $fraction = rtrim(substr($digits, $point), '0');
        $out = ('' === $whole ? '0' : $whole) . ('' === $fraction ? '' : '.' . $fraction);
        return ('-' === $m[1] && '0' !== $out) ? '-' . $out : $out;
    }

    /**
     * The shortest text that reads back as the same float, independent of the "precision" and "serialize_precision"
     * ini settings.
     *
     * @param float $number
     * @return string
     */
    private static function _shortestFloat(float $number): string
    {
        for ($digits = 0; $digits < 16; $digits++) {
            $text = sprintf('%.' . $digits . 'e', $number);
            if ((float)$text === $number) {
                return $text;
            }
        }
        return sprintf('%.16e', $number);
    }
}

<?php return ob_get_clean();
