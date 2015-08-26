<?php namespace PHPFHIR\Utilities;

use DCarbone\XMLPrimitiveTypes\XMLPrimitiveTypeFactory;
use PHPFHIR\Enum\PrimitivePropertyTypesEnum;

/**
 * Class PrimitiveTypeUtils
 * @package PHPFHIR\Utilities
 */
abstract class PrimitiveTypeUtils
{
    private static $XMLPrimitiveTypeFactory = null;

    /**
     * @param PrimitivePropertyTypesEnum $type
     * @return string
     */
    public static function getSimpleTypeVariableType(PrimitivePropertyTypesEnum $type)
    {
        $strType = (string)$type;
        switch($strType)
        {
            case PrimitivePropertyTypesEnum::BOOLEAN:
            case PrimitivePropertyTypesEnum::INTEGER:
            case PrimitivePropertyTypesEnum::STRING:
                return $strType;

            case PrimitivePropertyTypesEnum::DECIMAL:
                return 'float';

            case PrimitivePropertyTypesEnum::UUID:
            case PrimitivePropertyTypesEnum::OID:
            case PrimitivePropertyTypesEnum::ID:
            case PrimitivePropertyTypesEnum::XML_ID_REF:
            case PrimitivePropertyTypesEnum::URI:
            case PrimitivePropertyTypesEnum::BASE_64_BINARY:
            case PrimitivePropertyTypesEnum::CODE:
                return 'string';

            case PrimitivePropertyTypesEnum::INSTANT:
            case PrimitivePropertyTypesEnum::DATE:
            case PrimitivePropertyTypesEnum::DATETIME:
                return '\\DateTime';

            default:
                throw new \RuntimeException('No variable type mapping exists for simple property "'.$strType.'"');
        }
    }

    /**
     * @param PrimitivePropertyTypesEnum $type
     * @return null|string
     */
    public static function getSimpleTypeVariableTypeHintingValue(PrimitivePropertyTypesEnum $type)
    {
        switch((string)$type)
        {
            case PrimitivePropertyTypesEnum::INSTANT:
            case PrimitivePropertyTypesEnum::DATE:
            case PrimitivePropertyTypesEnum::DATETIME:
                return '\\DateTime';

            default:
                return null;
        }
    }

    /**
     * @param string $xmlDataType
     * @return \DCarbone\XMLPrimitiveTypes\Types\AbstractXMLPrimitiveType
     */
    public static function getXMLPrimitiveTypeClass($xmlDataType)
    {
        if (!isset(self::$XMLPrimitiveTypeFactory))
            self::$XMLPrimitiveTypeFactory = new XMLPrimitiveTypeFactory();

        return self::$XMLPrimitiveTypeFactory->getPrimitiveType($xmlDataType);
    }
}