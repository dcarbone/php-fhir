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

use DCarbone\PHPFHIR\ClassGenerator\Enum\PrimitivePropertyTypesEnum;

/**
 * Class PrimitiveTypeUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class PrimitiveTypeUtils
{
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

            case PrimitivePropertyTypesEnum::TIME:
            case PrimitivePropertyTypesEnum::INSTANT:
            case PrimitivePropertyTypesEnum::DATE:
            case PrimitivePropertyTypesEnum::DATETIME:

            case PrimitivePropertyTypesEnum::MARKDOWN:
            case PrimitivePropertyTypesEnum::UUID:
            case PrimitivePropertyTypesEnum::OID:
            case PrimitivePropertyTypesEnum::ID:
            case PrimitivePropertyTypesEnum::XML_ID_REF:
            case PrimitivePropertyTypesEnum::URI:
            case PrimitivePropertyTypesEnum::BASE_64_BINARY:
            case PrimitivePropertyTypesEnum::CODE:
                return 'string';

            // TODO: At somepoint, would looooove to turn this value into a real DateTime object...
//            case PrimitivePropertyTypesEnum::TIME:
//            case PrimitivePropertyTypesEnum::INSTANT:
//            case PrimitivePropertyTypesEnum::DATE:
//            case PrimitivePropertyTypesEnum::DATETIME:
//                return '\\DateTime';

            default:
                throw new \RuntimeException('No variable type mapping exists for simple property "'.$strType.'"');
        }
    }
}