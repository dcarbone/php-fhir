<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$primitiveType = $type->getPrimitiveType();

ob_start(); ?>
    /**
     * @return <?php echo TypeHintUtils::primitivePHPValueTypeHint($version, $primitiveType, true); ?>

     */
    public function getValue(): <?php echo TypeHintUtils::primitivePHPValueTypeHint($version, $primitiveType, true); ?>

    {
        return $this->value;
    }

<?php
$typeFile = null;
switch ($primitiveType) {
    // string types
    case PrimitiveTypeEnum::CANONICAL:
    case PrimitiveTypeEnum::CODE:
    case PrimitiveTypeEnum::STRING:
    case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
    case PrimitiveTypeEnum::ID:
    case PrimitiveTypeEnum::OID:
    case PrimitiveTypeEnum::URI:
    case PrimitiveTypeEnum::MARKDOWN: // TODO: markdown lib, maybe?
    case PrimitiveTypeEnum::UUID: // TODO: implement uuid lib?
    case PrimitiveTypeEnum::URL: // TODO: create specific URL type?
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::BOOLEAN:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/bool_type.php';
        break;

    // int types
    case PrimitiveTypeEnum::INTEGER:
    case PrimitiveTypeEnum::INTEGER64:
    case PrimitiveTypeEnum::POSITIVE_INTEGER:
    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/integer_type.php';
        break;

    // treat uint64's as strings for the moment.
    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/unsigned_integer_type.php';
        break;

    case PrimitiveTypeEnum::DECIMAL:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/decimal_type.php';
        break;

    // date types
    case PrimitiveTypeEnum::DATE:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/date_type.php';
        break;
    case PrimitiveTypeEnum::DATETIME:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/datetime_type.php';
        break;
    case PrimitiveTypeEnum::TIME:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/time_type.php';
        break;
    case PrimitiveTypeEnum::INSTANT:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/instant_type.php';
        break;

    case PrimitiveTypeEnum::BASE64_BINARY:
        // TODO: add content decoding?
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive/base64_binary_type.php';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}

echo require_with(
    $typeFile,
    [
        'version' => $version,
        'type' => $type,
        'primitiveType' => $primitiveType
    ]
);

return ob_get_clean();