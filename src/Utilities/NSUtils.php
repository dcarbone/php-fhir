<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\ComplexClassTypesEnum;
use PHPFHIR\Enum\SimpleClassTypesEnum;

/**
 * Class NSUtils
 * @package PHPFHIR\Utilities
 */
abstract class NSUtils
{
    /** @var array */
    private static $_simpleNSMap = array(
        'primitive' => 'Primitive',
        'list' => 'List',
        '' => '',
    );

    /**
     * @param string|null $outputNS
     * @param string|null $classNS
     * @return string
     */
    public static function generateRootNamespace($outputNS, $classNS)
    {
        $outputNS = (string)$outputNS;
        $classNS = (string)$classNS;

        if ('' === $outputNS && '' === $classNS)
            return '';

        if ('' === $outputNS)
            return $classNS;

        if ('' === $classNS)
            return $outputNS;

        return sprintf('%s\\%s', $outputNS, $classNS);
    }

    /**
     * @param SimpleClassTypesEnum $type
     * @return string
     */
    public static function getSimpleTypeNamespace(SimpleClassTypesEnum $type)
    {
        return self::$_simpleNSMap[(string)$type];
    }

    /**
     * @param string $name
     * @param ComplexClassTypesEnum|null $type
     * @return string
     */
    public static function getComplexTypeNamespace($name, ComplexClassTypesEnum $type = null)
    {
        switch((string)$type)
        {
            case 'Resource':
                return 'Resource';
            case 'Element':
                return 'Element';

            case 'Component':
                $root = strstr($name, '.', true);
                return sprintf('Resource\\%s', $root);

            default:
                return '';
        }
    }
}