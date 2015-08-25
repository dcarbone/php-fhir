<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\SimplePropertyTypesEnum;

/**
 * Class SimpleTypeUtils
 * @package PHPFHIR\Utilities
 */
abstract class SimpleTypeUtils
{
    /**
     * @param SimplePropertyTypesEnum $type
     * @return string
     */
    public static function getSimpleTypeVariableType(SimplePropertyTypesEnum $type)
    {
        $strType = (string)$type;
        switch($strType)
        {
            case SimplePropertyTypesEnum::BOOLEAN:
            case SimplePropertyTypesEnum::INTEGER:
            case SimplePropertyTypesEnum::STRING:
                return $strType;

            case SimplePropertyTypesEnum::DECIMAL:
                return 'float';

            case SimplePropertyTypesEnum::UUID:
            case SimplePropertyTypesEnum::OID:
            case SimplePropertyTypesEnum::ID:
            case SimplePropertyTypesEnum::XML_ID_REF:
            case SimplePropertyTypesEnum::URI:
            case SimplePropertyTypesEnum::BASE_64_BINARY:
            case SimplePropertyTypesEnum::CODE:
                return 'string';

            case SimplePropertyTypesEnum::INSTANT:
            case SimplePropertyTypesEnum::DATE:
            case SimplePropertyTypesEnum::DATETIME:
                return '\\DateTime';

            default:
                throw new \RuntimeException('No variable type mapping exists for simple property "'.$strType.'"');
        }
    }

    /**
     * @param SimplePropertyTypesEnum $type
     * @return null|string
     */
    public static function getSimpleTypeVariableTypeHintingValue(SimplePropertyTypesEnum $type)
    {
        switch((string)$type)
        {
            case SimplePropertyTypesEnum::INSTANT:
            case SimplePropertyTypesEnum::DATE:
            case SimplePropertyTypesEnum::DATETIME:
                return '\\DateTime';

            default:
                return null;
        }
    }
}