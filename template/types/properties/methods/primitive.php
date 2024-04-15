<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$primitiveType = $type->getPrimitiveType();

ob_start(); ?>
    /**
     * @return <?php echo TypeHintUtils::primitivePHPReturnValueTypeDoc($config, $primitiveType, true, false); ?>

     */
    public function getValue(): <?php echo TypeHintUtils::primitivePHPValueTypeHint($config, $primitiveType, true); ?>

    {
        return $this->value;
    }

<?php
$typeFile = null;
switch ($primitiveType) {
    // string types
    case PrimitiveType::CANONICAL:
    case PrimitiveType::CODE:
    case PrimitiveType::STRING:
    case PrimitiveType::SAMPLE_DATA_TYPE:
    case PrimitiveType::ID:
    case PrimitiveType::OID:
    case PrimitiveType::URI:
    case PrimitiveType::MARKDOWN: // TODO: markdown lib, maybe?
    case PrimitiveType::UUID: // TODO: implement uuid lib?
    case PrimitiveType::URL: // TODO: create specific URL type?
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'string_type.php';
        break;

    case PrimitiveType::BOOLEAN:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'bool_type.php';
        break;

    // int types
    case PrimitiveType::INTEGER:
    case PrimitiveType::POSITIVE_INTEGER:
    case PrimitiveType::NEGATIVE_INTEGER:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'integer_type.php';
        break;

    // treat uint64's as strings for the moment.
    case PrimitiveType::UNSIGNED_INTEGER:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'unsigned_integer_type.php';
        break;

    case PrimitiveType::DECIMAL:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'decimal_type.php';
        break;

    // date types
    case PrimitiveType::DATE:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'date_type.php';
        break;
    case PrimitiveType::DATETIME:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'datetime_type.php';
        break;
    case PrimitiveType::TIME:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'time_type.php';
        break;
    case PrimitiveType::INSTANT:
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'instant_type.php';
        break;

    case PrimitiveType::BASE_64_BINARY:
        // TODO: add content decoding?
        $typeFile = __DIR__ . DIRECTORY_SEPARATOR . 'primitive' . DIRECTORY_SEPARATOR . 'base64_binary_type.php';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}

echo require_with(
    $typeFile,
    [
        'config' => $config,
        'type' => $type,
        'primitiveType' => $primitiveType
    ]
);

return ob_get_clean();