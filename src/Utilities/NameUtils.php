<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
        'fhir' => 'FHIR',
        'xhtml' => 'XHTML',
        'xml' => 'XML',
        'json' => 'JSON',
        'http' => 'HTTP',
        'curl' => 'CURL',
        'dstu1' => 'DSTU1',
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
