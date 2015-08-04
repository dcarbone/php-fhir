<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\ComplexClassTypesEnum;
use PHPFHIR\Enum\SimpleClassTypesEnum;

/**
 * Class NSUtils
 * @package PHPFHIR\Utilities
 */
abstract class NSUtils
{
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
        switch((string)$type)
        {
            case SimpleClassTypesEnum::_LIST:
                return 'FHIRList';
            case SimpleClassTypesEnum::PRIMITIVE:
                return 'FHIRPrimitive';

            default:
                return '';
        }
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
            case ComplexClassTypesEnum::RESOURCE:
                return 'FHIRResource';
            case ComplexClassTypesEnum::ELEMENT:
                return 'FHIRElement';
            case ComplexClassTypesEnum::COMPONENT:
                return sprintf('FHIRResource\\FHIR%s', strstr($name, '.', true));

            default:
                return '';
        }
    }
}