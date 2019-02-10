<?php namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Class PrimitiveTypeEnum
 * @package DCarbone\PHPFHIR\Enum
 */
class PrimitiveTypeEnum extends Enum
{
    const STRING  = 'string';
    const BOOLEAN = 'boolean';
    const INTEGER = 'integer';
    const DECIMAL = 'decimal';

    const POSITIVE_INTEGER = 'positiveInt';
    const NEGATIVE_INTEGER = 'negativeInt';
    const UNSIGNED_INTEGER = 'unsignedInt';

    const DATE     = 'date';
    const DATETIME = 'dateTime';
    const TIME     = 'time';
    const INSTANT  = 'instant';

    const CODE      = 'code';
    const OID       = 'oid';
    const CANONICAL = 'canonical';
    const URI       = 'uri';
    const URL       = 'url';
    const ID        = 'id';
    const UUID      = 'uuid';

    const BASE_64_BINARY   = 'base64Binary';
    const MARKDOWN         = 'markdown';
    const SAMPLE_DATA_TYPE = 'SampledDataDataType';
}
