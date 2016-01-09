<?php namespace PHPFHIR\ClassGenerator\Utilities;

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

use DCarbone\XMLPrimitiveTypes\XMLPrimitiveTypeFactory;
use PHPFHIR\ClassGenerator\Enum\PrimitivePropertyTypesEnum;

/**
 * Class PrimitiveTypeUtils
 * @package PHPFHIR\ClassGenerator\Utilities
 */
abstract class PrimitiveTypeUtils
{
    /** @var XMLPrimitiveTypeFactory */
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


            case PrimitivePropertyTypesEnum::NEGATIVE_INTEGER:
            case PrimitivePropertyTypesEnum::POSITIVE_INTEGER:
            case PrimitivePropertyTypesEnum::UNSIGNED_INTEGER:
                return 'integer';

            case PrimitivePropertyTypesEnum::DECIMAL:
                return 'float';

            case PrimitivePropertyTypesEnum::MARKDOWN:
            case PrimitivePropertyTypesEnum::UUID:
            case PrimitivePropertyTypesEnum::OID:
            case PrimitivePropertyTypesEnum::ID:
            case PrimitivePropertyTypesEnum::XML_ID_REF:
            case PrimitivePropertyTypesEnum::URI:
            case PrimitivePropertyTypesEnum::BASE_64_BINARY:
            case PrimitivePropertyTypesEnum::CODE:
                return 'string';

            case PrimitivePropertyTypesEnum::TIME:
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