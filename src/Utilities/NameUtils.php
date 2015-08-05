<?php namespace PHPFHIR\Utilities;

/**
 * Class NameUtils
 * @package PHPFHIR\Utilities
 */
abstract class NameUtils
{
    const PROPNAME_REGEX = '{^[a-zA-Z0-9_]+$}S';
    const FUNCNAME_REGEX = '{^[a-zA-Z0-9_]+$}S';
    const CLASSNAME_REGEX = '{^[a-zA-Z0-9_]+$}S';
    const NSNAME_REGEX = '{^[a-zA-Z\\\_]+$}S';

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidPropertyName($name)
    {
        return (bool)preg_match(self::PROPNAME_REGEX, $name);
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
        if (false !== strpos($name, '-'))
            $name = strstr($name, '-', true);

        if (preg_match('{^[a-z]}S', $name))
            $name = ucfirst($name);

        return sprintf('FHIR%s', $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getComplexTypeClassName($name)
    {
        return sprintf('FHIR%s', ucfirst(str_replace('.', '', $name)));
    }
}