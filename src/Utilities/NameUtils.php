<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Version\Definition\Type;

/**
 * Class NameUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
class NameUtils
{
    public const VARIABLE_NAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]*$}S';
    public const FUNCTION_NAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]*$}S';
    public const CLASSNAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]*$}S';
    public const NAMESPACE_REGEX = '{^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z0-9_]+)*[a-zA-Z0-9_]$}';

    /** @var array */
    public static array $classNameSearch = [
        '.',
        '-',
    ];

    /** @var array */
    public static array $classNameReplace = [
        '',
        '_',
    ];

    /** @var array */
    private const _UPPER = [
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z',
    ];
    /** @var array */
    private const _LOWER = [
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z',
    ];

    /** @var array */
    private const _NUMS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /** @var array */
    private const _PUNCTUATION_MAP = [
        '.' => '_DOT_',
        '-' => '_HYPHEN_',
    ];

    private const _NAME_REPLACE_MAP = [
        'api' => 'API',
        'curl' => 'CURL',
        'dstu1' => 'DSTU1',
        'fhir' => 'FHIR',
        'http' => 'HTTP',
        'id' => 'ID',
        'json' => 'JSON',
        'xhtml' => 'XHTML',
        'xml' => 'XML',
    ];

    /** @var array */
    private static array $constNameMap = [];

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidVariableName(string $name): bool
    {
        return (bool)preg_match(self::VARIABLE_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidFunctionName(string $name): bool
    {
        return (bool)preg_match(self::FUNCTION_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidClassName(string $name): bool
    {
        return (bool)preg_match(self::CLASSNAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidNSName(string $name): bool
    {
        return '' !== $name && preg_match(self::NAMESPACE_REGEX, $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getTypeClassName(string $name): string
    {
        if (false !== ($pos = strpos($name, '-primitive'))) {
            $name = sprintf('%sPrimitive', substr($name, 0, $pos));
        } elseif (false !== ($pos = strpos($name, '-list'))) {
            $name = sprintf('%sList', substr($name, 0, $pos));
        }

        if (preg_match('{^[a-z]}S', $name)) {
            $name = ucfirst($name);
        }

        return sprintf('FHIR%s', str_replace(self::$classNameSearch, self::$classNameReplace, $name));
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getConstName(string $name): string
    {
        if (isset(self::$constNameMap[$name])) {
            return self::$constNameMap[$name];
        }

        $constName = '';
        $lastUpper = false;
        foreach (str_split($name) as $chr) {
            if (in_array($chr, self::_UPPER, true) || in_array($chr, self::_NUMS, true)) {
                // really simplistic abbreviation detection...
                if ('' !== $constName && !$lastUpper && !str_ends_with($constName, '_')) {
                    $constName .= '_';
                }
                $constName .= $chr;
                $lastUpper = true;
            } elseif (in_array($chr, self::_LOWER, true)) {
                $constName .= strtoupper($chr);
                $lastUpper = false;
            } elseif (in_array($chr, self::_NUMS, true)) {
                $constName .= $chr;
                $lastUpper = false;
            } elseif (isset(self::_PUNCTUATION_MAP[$chr])) {
                $constName .= self::_PUNCTUATION_MAP[$chr];
                $lastUpper = false;
            } else {
                $constName .= '_';
                $lastUpper = false;
            }
        }
        return self::$constNameMap[$name] = $constName;
    }

    /**
     * @param string $fieldName FHIR name of a field, e.g. "birthDate".
     * @return string Name of the constant holding the field name, e.g. "FIELD_BIRTH_DATE".
     */
    public static function getFieldConstantName(string $fieldName): string
    {
        return 'FIELD_' . self::getConstName($fieldName);
    }

    /**
     * @param string $fieldName FHIR name of a field, e.g. "birthDate".
     * @return string Name of the field's getter method, e.g. "getBirthDate".
     */
    public static function getGetterName(string $fieldName): string
    {
        return 'get' . ucfirst($fieldName);
    }

    /**
     * Joins the alphanumeric runs of $name, each with its first letter upper cased, e.g. "us-core.patient" =>
     * "UsCorePatient".  The result may be empty, or start with a digit.
     *
     * @param string $name
     * @return string
     */
    public static function toPascalCase(string $name): string
    {
        $out = '';
        foreach (preg_split('{[^A-Za-z0-9]+}', $name, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $out .= ucfirst($part);
        }
        return $out;
    }

    /**
     * Derives a valid class name from arbitrary text, see {@see self::toPascalCase()}.
     *
     * @param string $name
     * @param string $digitPrefix Prepended when the name would otherwise start with a digit.
     * @return string
     * @throws \DomainException If no valid class name can be derived.
     */
    public static function sanitizeClassName(string $name, string $digitPrefix = ''): string
    {
        $out = self::toPascalCase($name);
        if ('' !== $out && ctype_digit($out[0])) {
            $out = $digitPrefix . $out;
        }
        if (!self::isValidClassName($out)) {
            throw new \DomainException(sprintf('Cannot derive a valid class name from "%s", got "%s".', $name, $out));
        }
        return $out;
    }

    /**
     * @param string $fqn Fully qualified class name, with or without leading slash.
     * @return array{0: string, 1: string} Namespace, without leading slash and empty if there is none, and class name.
     */
    public static function splitFullyQualifiedName(string $fqn): array
    {
        $fqn = ltrim($fqn, PHPFHIR_NAMESPACE_SEPARATOR);
        $pos = strrpos($fqn, PHPFHIR_NAMESPACE_SEPARATOR);
        if (false === $pos) {
            return ['', $fqn];
        }
        return [substr($fqn, 0, $pos), substr($fqn, $pos + 1)];
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function getTypeXMLElementName(Type $type): string
    {
        return str_replace(self::$classNameSearch, self::$classNameReplace, $type->getFHIRName());
    }

    /**
     * This is a horrendously named function.
     *
     * @param string $bit
     * @return string
     */
    public static function phpNameFormat(string $bit): string
    {
        return self::_NAME_REPLACE_MAP[$bit] ?? ucfirst($bit);
    }

    public static function templateFilenameToPHPName(string $filename, string $explode = '_', string $join = ''): string
    {
        $parts = [];
        foreach (explode($explode, $filename) as $bit) {
            $parts[] = self::phpNameFormat($bit);
        }
        return implode($join, array_filter($parts));
    }
}
