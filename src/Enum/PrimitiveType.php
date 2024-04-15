<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
enum PrimitiveType:string
{
    use ValuesTrait;

    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
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

    case BASE_64_BINARY = 'base64Binary';
    case MARKDOWN = 'markdown';
    case SAMPLE_DATA_TYPE = 'SampledDataDataType';

    /**
     * @return string
     */
    public function getPHPValueTypes(): string
    {
        // leaving as switch for readability purposes.
        switch ($this) {
            case PrimitiveType::STRING:
            case PrimitiveType::BOOLEAN:
            case PrimitiveType::INTEGER:
                return $this->value;

            case PrimitiveType::DECIMAL:
                return 'double';

            case PrimitiveType::POSITIVE_INTEGER:
            case PrimitiveType::NEGATIVE_INTEGER:
                return 'integer';

            case PrimitiveType::DATE:
            case PrimitiveType::DATETIME:
            case PrimitiveType::TIME:
            case PrimitiveType::INSTANT:
            case PrimitiveType::CODE:
            case PrimitiveType::OID:
            case PrimitiveType::CANONICAL:
            case PrimitiveType::URI:
            case PrimitiveType::URL:
            case PrimitiveType::ID:
            case PrimitiveType::UUID:
            case PrimitiveType::SAMPLE_DATA_TYPE:
            case PrimitiveType::BASE_64_BINARY: // TODO: add content decoding?
            case PrimitiveType::MARKDOWN: // TODO: markdown lib, maybe?
            case PrimitiveType::UNSIGNED_INTEGER: // TODO: utilize big number lib, maybe?
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
            'double' => 'float',
            'integer' => 'int',
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
            case PrimitiveType::DATE:
            case PrimitiveType::DATETIME:
            case PrimitiveType::INSTANT:
            case PrimitiveType::TIME:
                $hintTypes[] = '\\DateTimeInterface';
                break;

            // floats may stem from integers
            case PrimitiveType::DECIMAL:
                $hintTypes[] = 'int';
                break;

            // integers may stem from floats
            case PrimitiveType::INTEGER:
                $hintTypes[] = 'float';
                break;

            // unsigned integers may stem from integers or floats
            case PrimitiveType::UNSIGNED_INTEGER:
                array_push($hintTypes, 'int', 'float');
                break;
        }

        return $hintTypes;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveType|string ...$other
     * @return bool
     */
    public function isOneOf(PrimitiveType|string ...$other): bool
    {
        $vals = self::values();
        foreach ($other as $name) {
            if ($this === $name || in_array($name, $vals, true)) {
                return true;
            }
        }

        return false;
    }
}
