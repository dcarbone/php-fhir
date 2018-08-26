<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\PrimitiveType;

/**
 * Class PrimitiveTypeUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class PrimitiveTypeUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveType $type
     * @return string
     */
    public static function getSimpleTypeVariableType(PrimitiveType $type)
    {
        $strType = (string)$type;
        switch ($strType) {
            case PrimitiveType::BOOLEAN:
            case PrimitiveType::INTEGER:
            case PrimitiveType::STRING:
                return $strType;

            case PrimitiveType::NEGATIVE_INTEGER:
            case PrimitiveType::POSITIVE_INTEGER:
            case PrimitiveType::UNSIGNED_INTEGER:
                return 'integer';

            case PrimitiveType::DECIMAL:
                return 'float';

            case PrimitiveType::TIME:
            case PrimitiveType::INSTANT:
            case PrimitiveType::DATE:
            case PrimitiveType::DATETIME:

            case PrimitiveType::MARKDOWN:
            case PrimitiveType::UUID:
            case PrimitiveType::OID:
            case PrimitiveType::ID:
            case PrimitiveType::XML_ID_REF:
            case PrimitiveType::URI:
            case PrimitiveType::BASE_64_BINARY:
            case PrimitiveType::CODE:
                return 'string';

            // TODO: At some point, would looooove to turn this value into a real DateTime object...
//            case PrimitivePropertyTypesEnum::TIME:
//            case PrimitivePropertyTypesEnum::INSTANT:
//            case PrimitivePropertyTypesEnum::DATE:
//            case PrimitivePropertyTypesEnum::DATETIME:
//                return '\\DateTime';

            default:
                throw new \RuntimeException('No variable type mapping exists for simple property "' . $strType . '"');
        }
    }
}