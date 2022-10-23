<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class PrimitiveTypeEnum
 * @package DCarbone\PHPFHIR\Enum
 */
class PrimitiveTypeEnum extends AbstractEnum
{
    public const STRING  = 'string';
    public const BOOLEAN = 'boolean';
    public const INTEGER = 'integer';
    public const DECIMAL = 'decimal';

    public const POSITIVE_INTEGER = 'positiveInt';
    public const NEGATIVE_INTEGER = 'negativeInt';
    public const UNSIGNED_INTEGER = 'unsignedInt';

    public const DATE     = 'date';
    public const DATETIME = 'dateTime';
    public const TIME     = 'time';
    public const INSTANT  = 'instant';

    public const CODE      = 'code';
    public const OID       = 'oid';
    public const CANONICAL = 'canonical';
    public const URI       = 'uri';
    public const URL       = 'url';
    public const ID        = 'id';
    public const UUID      = 'uuid';

    public const BASE_64_BINARY   = 'base64Binary';
    public const MARKDOWN         = 'markdown';
    public const SAMPLE_DATA_TYPE = 'SampledDataDataType';

    /**
     * @return string
     */
    public function getPHPValueType(): string
    {
        switch ($v = $this->getValue()) {
            case PrimitiveTypeEnum::STRING:
            case PrimitiveTypeEnum::BOOLEAN:
            case PrimitiveTypeEnum::INTEGER:
                return $v;

            case PrimitiveTypeEnum::DECIMAL:
                return 'double';

            case PrimitiveTypeEnum::POSITIVE_INTEGER:
            case PrimitiveTypeEnum::NEGATIVE_INTEGER:
                return 'integer';

            case PrimitiveTypeEnum::DATE:
            case PrimitiveTypeEnum::DATETIME:
            case PrimitiveTypeEnum::TIME:
            case PrimitiveTypeEnum::INSTANT:
            case PrimitiveTypeEnum::CODE:
            case PrimitiveTypeEnum::OID:
            case PrimitiveTypeEnum::CANONICAL:
            case PrimitiveTypeEnum::URI:
            case PrimitiveTypeEnum::URL:
            case PrimitiveTypeEnum::ID:
            case PrimitiveTypeEnum::UUID:
            case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
            case PrimitiveTypeEnum::BASE_64_BINARY: // TODO: add content decoding?
            case PrimitiveTypeEnum::MARKDOWN: // TODO: markdown lib, maybe?
            case PrimitiveTypeEnum::UNSIGNED_INTEGER: // TODO: utilize big number lib, maybe?
                return 'string';

            default:
                throw ExceptionUtils::createUnknownPrimitiveTypeEnumException($this);
        }
    }
}
