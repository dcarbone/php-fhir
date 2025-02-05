<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

enum PrimitiveTypeEnum: string
{
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case INTEGER64 = 'integer64';
    case DECIMAL = 'decimal';

    case POSITIVE_INTEGER = 'positiveInt';
    case NEGATIVE_INTEGER = 'negativeInt';
    case UNSIGNED_INTEGER = 'unsignedInt';

    case DATE = 'date';
    case DATETIME = 'dateTime';
    case TIME = 'time';
    case INSTANT = 'instant';

    case CODE = 'code';
    case OID = 'oid';
    case CANONICAL = 'canonical';
    case URI = 'uri';
    case URL = 'url';
    case ID = 'id';
    case UUID = 'uuid';

    case BASE64_BINARY = 'base64Binary';
    case XS_BASE64_BINARY = 'xs:base64Binary';
    case MARKDOWN = 'markdown';
    case SAMPLE_DATA_TYPE = 'SampledDataDataType';

    /**
     * @return string
     */
    public function getPHPValueTypes(): string
    {
        // leaving as switch for readability purposes.
        switch ($this) {
            case PrimitiveTypeEnum::STRING:
            case PrimitiveTypeEnum::BOOLEAN:
            case PrimitiveTypeEnum::INTEGER:
                return $this->value;

            case PrimitiveTypeEnum::DECIMAL:
                return 'double';

            case PrimitiveTypeEnum::POSITIVE_INTEGER:
            case PrimitiveTypeEnum::NEGATIVE_INTEGER:
            case PrimitiveTypeEnum::INTEGER64:
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
            case PrimitiveTypeEnum::BASE64_BINARY: // TODO: add content decoding?
            case PrimitiveTypeEnum::MARKDOWN: // TODO: markdown lib, maybe?
            case PrimitiveTypeEnum::UNSIGNED_INTEGER: // TODO: utilize big number lib, maybe?
                return 'string';

            default:
                throw new \DomainException(sprintf('No PHP value type case for "%s"', $this->value));
        }
    }

    /**
     * @return string
     */
    public function getPHPReturnValueTypeHint(): string
    {
        return match ($hint = $this->getPHPValueTypes()) {
            'boolean' => 'bool',
            'double', 'integer' => 'string',
            default => $hint,
        };
    }

    /**
     * @return string[]
     */
    public function getPHPReceiveValueTypeHints(): array
    {
        $hintTypes = [$this->getPHPReturnValueTypeHint()];

        // make sure 'string' is second item list.
        if (!in_array('string', $hintTypes, true)) {
            array_unshift($hintTypes, 'string');
        }

        switch ($this) {
            // Date types may always accept a \DateTimeInterface instance
            case PrimitiveTypeEnum::DATE:
            case PrimitiveTypeEnum::DATETIME:
            case PrimitiveTypeEnum::INSTANT:
            case PrimitiveTypeEnum::TIME:
                $hintTypes[] = '\\DateTimeInterface';
                break;

            // floats may stem from integers
            case PrimitiveTypeEnum::DECIMAL:
                array_push($hintTypes, 'int', 'float');
                break;

            // integers may stem from floats
            case PrimitiveTypeEnum::INTEGER:
            case PrimitiveTypeEnum::INTEGER64:
            case PrimitiveTypeEnum::POSITIVE_INTEGER:
            case PrimitiveTypeEnum::NEGATIVE_INTEGER:
                $hintTypes[] = 'float';
                break;

            // unsigned integers may stem from integers or floats
            case PrimitiveTypeEnum::UNSIGNED_INTEGER:
                array_push($hintTypes, 'int', 'float');
                break;
        }

        return $hintTypes;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum|string ...$other
     * @return bool
     */
    public function isOneOf(PrimitiveTypeEnum|string ...$other): bool
    {
        return in_array($this, $other, true) || in_array($this->value, $other, true);
    }
}
