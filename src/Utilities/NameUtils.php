<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class NameUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class NameUtils
{
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

    /** @var array */
    private static array $constNameMap = [];

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidVariableName(string $name): bool
    {
        return (bool)preg_match(PHPFHIR_VARIABLE_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidFunctionName(string $name): bool
    {
        return (bool)preg_match(PHPFHIR_FUNCTION_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidClassName(string $name): bool
    {
        return (bool)preg_match(PHPFHIR_CLASSNAME_REGEX, $name);
    }

    /**
     * @param string|null $name
     * @return bool
     */
    public static function isValidNSName(?string $name): bool
    {
        return null === $name || '' === $name || preg_match(PHPFHIR_NAMESPACE_REGEX, $name);
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
                if ('' !== $constName && !$lastUpper && '_' !== substr(
                        $constName,
                        -1
                    )) { // really simplistic abbreviation detection...
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
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function getTypeXMLElementName(Type $type): string
    {
        return str_replace(self::$classNameSearch, self::$classNameReplace, $type->getFHIRName());
    }

    /**
     * @param string $propName
     * @return string
     */
    public static function getPropertyMethodName(string $propName): string
    {
        return ucfirst($propName);
    }

    /**
     * @param string $propName
     * @return string
     */
    public static function getPropertyVariableName(string $propName): string
    {
        return sprintf('$%s', $propName);
    }
}
