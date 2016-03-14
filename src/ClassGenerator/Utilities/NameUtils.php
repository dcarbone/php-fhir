<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class NameUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class NameUtils
{
    const VARIABLE_NAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]+$}S';
    const FUNCNAME_REGEX = '{^[a-zA-Z0-9_]+$}S';
    const CLASSNAME_REGEX = '{^[a-zA-Z0-9_]+$}S';
    const NSNAME_REGEX = '{^[a-zA-Z\\\_]+$}S';

    /** @var array */
    public static $classNameSearch = array(
        '.',
        '-',
    );

    /** @var array */
    public static $classNameReplace = array(
        '',
        '_'
    );

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidVariableName($name)
    {
        return (bool)preg_match(self::VARIABLE_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidFunctionName($name)
    {
        return (bool)preg_match(self::FUNCNAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidClassName($name)
    {
        return (bool)preg_match(self::CLASSNAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidNSName($name)
    {
        return null === $name || '' === $name || (bool)preg_match(self::NSNAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getSimpleTypeClassName($name)
    {
        if (false !== ($pos = strpos($name, '-primitive')))
            $name = sprintf('%sPrimitive', substr($name, 0, $pos));
        else if (false !== ($pos = strpos($name, '-list')))
            $name = sprintf('%sList', substr($name, 0, $pos));

        if (preg_match('{^[a-z]}S', $name))
            $name = ucfirst($name);

        return sprintf('FHIR%s', str_replace(self::$classNameSearch, self::$classNameReplace, $name));
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getComplexTypeClassName($name)
    {
        return sprintf('FHIR%s', ucfirst(str_replace(self::$classNameSearch, self::$classNameReplace, $name)));
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