<?php

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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassName = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$fhirName = $type->getFHIRName();
$sortedProperties = $type->getProperties()->getSortedIterator();
$classDocumentation = trim($type->getDocBlockDocumentationFragment(1, true));
$xmlName = NameUtils::getTypeXMLElementName($type);

$primitiveType = $type->getPrimitiveType();
$phpValueType = $primitiveType->getPHPValueType();

// begin output buffer
ob_start();

// first, build file header
echo require PHPFHIR_TEMPLATE_COMMON_DIR . '/file_header.php';

// next, build class header ?>
/**
<?php if ('' !== $classDocumentation) : ?>
 *<?php echo $classDocumentation; ?>
 *<?php endif; ?>
 * Class <?php echo $typeClassName; ?>

 * @package <?php echo $fqns; ?>

 */
class <?php echo $typeClassName; ?><?php echo null !== $parentType ? " extends {$parentType->getClassName()}" : '' ?> implements \JsonSerializable
{
    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = '<?php echo $fhirName; ?>';

    /** @var null|<?php echo $phpValueType; ?> */
    private $value = null;

<?php
switch($primitiveType->getValue()) {
    case PrimitiveTypeEnum::STRING:
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::BOOLEAN:
        echo require __DIR__ . '/primitive/bool_type.php';
        break;

    case PrimitiveTypeEnum::INTEGER:
    case PrimitiveTypeEnum::POSITIVE_INTEGER:
    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        echo require __DIR__ . '/primitive/integer_type.php';
        break;

    case PrimitiveTypeEnum::DECIMAL:
        echo require __DIR__ . '/primitive/decimal_type.php';
        break;

    case PrimitiveTypeEnum::DATE:
        echo require __DIR__ . '/primitive/date_type.php';
        break;
    case PrimitiveTypeEnum::DATETIME:
        echo require __DIR__ . '/primitive/datetime_type.php';
        break;
    case PrimitiveTypeEnum::TIME:
        echo require __DIR__ . '/primitive/time_type.php';
        break;
    case PrimitiveTypeEnum::INSTANT:
        echo require __DIR__ . '/primitive/instant_type.php';
        break;

    case PrimitiveTypeEnum::URI:
        echo require __DIR__.'/primitive/uri_type.php';
        break;

    case PrimitiveTypeEnum::CODE:
        echo require __DIR__ . '/primitive/code_type.php';
        break;

    case PrimitiveTypeEnum::OID:
        echo require __DIR__.'/primitive/oid_type.php';
        break;

    case PrimitiveTypeEnum::ID:
        echo require __DIR__.'/primitive/id_type.php';
        break;

    case PrimitiveTypeEnum::CANONICAL:
    case PrimitiveTypeEnum::URL:
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::UUID:
        // TODO: implement uuid lib?
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::BASE_64_BINARY:
        // TODO: add content decoding?
        echo require __DIR__ . '/primitive/base64_binary_type.php';
        break;

    case PrimitiveTypeEnum::MARKDOWN:
        // TODO: markdown lib, maybe?
        echo require __DIR__ . '/primitive/markdown_type.php';
        break;

    case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}
?>
<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR.'/xml_primitive_types.php'; ?>

<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR.'/json_primitive_types.php'; ?>

    /**
     * @return null|<?php $primitiveType->getPHPValueType(); ?>
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
<?php if (PrimitiveTypeEnum::BOOLEAN === $primitiveType->getValue()) : ?>
        return $this->getValue() ? 'true' : 'false';
<?php else : ?>
        return (string)$this->getValue();
<?php endif; ?>
    }
}<?php return ob_get_clean();