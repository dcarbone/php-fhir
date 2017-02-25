<?php namespace DCarbone\PHPFHIR\ClassGenerator\Enum;

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

use MyCLabs\Enum\Enum;

/**
 * Class PrimitivePropertyTypesEnum
 * @package DCarbone\PHPFHIR\ClassGenerator\Enum
 */
class PrimitivePropertyTypesEnum extends Enum
{
    const POSITIVE_INTEGER = 'positiveint';
    const NEGATIVE_INTEGER = 'negativeint';
    const UNSIGNED_INTEGER = 'unsignedint';
    const INTEGER = 'integer';
    const DECIMAL = 'decimal';

    const TIME = 'time';
    const INSTANT = 'instant';
    const DATE = 'date';
    const DATETIME = 'datetime';

    const BOOLEAN = 'boolean';
    const BASE_64_BINARY = 'base64binary';
    const STRING = 'string';
    const URI = 'uri';
    const CODE = 'code';
    const XML_ID_REF = 'xmlidref';
    const ID = 'id';
    const OID = 'oid';
    const UUID = 'uuid';
    const MARKDOWN = 'markdown';
}
