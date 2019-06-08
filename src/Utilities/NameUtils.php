<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    public static $classNameSearch = [
        '.',
        '-',
    ];

    /** @var array */
    public static $classNameReplace = [
        '',
        '_',
    ];

    private static $upper = [
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
    private static $lower = [
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
    private static $nums = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidVariableName($name)
    {
        return (bool)preg_match(PHPFHIR_VARIABLE_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidFunctionName($name)
    {
        return (bool)preg_match(PHPFHIR_FUNCTION_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidClassName($name)
    {
        return (bool)preg_match(PHPFHIR_CLASSNAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidNSName($name)
    {
        return null === $name || '' === $name || (bool)preg_match(PHPFHIR_NAMESPACE_REGEX, $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getTypeClassName($name)
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
    public static function getConstName($name)
    {
        // TODO: handle punctuation

        $constName = '';
        $lastUpper = false;
        foreach (str_split($name) as $chr) {
            if (in_array($chr, self::$upper, true) || in_array($chr, self::$nums, true)) {
                if ('' !== $constName && !$lastUpper && '_' !== substr($constName,
                        -1)) { // really simplistic abbreviation detection...
                    $constName .= '_';
                }
                $constName .= $chr;
                $lastUpper = true;
            } elseif (in_array($chr, self::$lower, true)) {
                $constName .= strtoupper($chr);
                $lastUpper = false;
            } elseif (in_array($chr, self::$nums, true)) {
                $constName .= $chr;
                $lastUpper = false;
            } else {
                $constName .= '_';
                $lastUpper = false;
            }
        }
        return $constName;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function getTypeXMLElementName(Type $type)
    {
        return str_replace(self::$classNameSearch, self::$classNameReplace, $type->getFHIRName());
    }

    /**
     * @param string $propName
     * @return string
     */
    public static function getPropertyMethodName($propName)
    {
        return ucfirst($propName);
    }

    /**
     * @param string $propName
     * @return string
     */
    public static function getPropertyVariableName($propName)
    {
        return sprintf('$%s', $propName);
    }
}
