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
$classDocumentation = trim($type->getDocBlockDocumentationFragment(1));
$xmlName = NameUtils::getTypeXMLElementName($type);

$primitiveType = $type->getPrimitiveType();

switch($primitiveType->getValue()) {
    case PrimitiveTypeEnum::STRING:
    case PrimitiveTypeEnum::BOOLEAN:
    case PrimitiveTypeEnum::INTEGER:
        $phpValueType = (string)$primitiveType;
        break;

    case PrimitiveTypeEnum::DECIMAL:
        $phpValueType = 'float';
        break;

    case PrimitiveTypeEnum::POSITIVE_INTEGER:
    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
        $phpValueType = 'integer';
        break;

    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        // TODO: utilize big number lib, maybe?
        $phpValueType = 'string';
        break;

    case PrimitiveTypeEnum::DATE:
    case PrimitiveTypeEnum::DATETIME:
    case PrimitiveTypeEnum::TIME:
    case PrimitiveTypeEnum::INSTANT:
        $phpValueType = '\\DateTime';
        break;

    case PrimitiveTypeEnum::CODE:
    case PrimitiveTypeEnum::OID:
    case PrimitiveTypeEnum::CANONICAL:
    case PrimitiveTypeEnum::URI:
    case PrimitiveTypeEnum::URL:
    case PrimitiveTypeEnum::ID:
    case PrimitiveTypeEnum::UUID:
        $phpValueType = 'string';
        break;

    case PrimitiveTypeEnum::BASE_64_BINARY:
        // TODO: add content decoding?
        $phpValueType = 'string';
        break;

    case PrimitiveTypeEnum::MARKDOWN:
        // TODO: markdown lib, maybe?
        $phpValueType = 'string';
        break;

    case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
        $phpValueType = 'string';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}

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
        echo require __DIR__ . '/primitive/integer_type.php';
        break;

    case PrimitiveTypeEnum::DECIMAL:
        echo require __DIR__ . '/primitive/float_type.php';
        break;

    case PrimitiveTypeEnum::POSITIVE_INTEGER:
    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
        echo require __DIR__ . '/primitive/integer_type.php';
        break;

    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        // TODO: utilize big number lib, maybe?
        echo require __DIR__ . '/primitive/string_type.php';
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

    case PrimitiveTypeEnum::CODE:
    case PrimitiveTypeEnum::OID:
    case PrimitiveTypeEnum::CANONICAL:
    case PrimitiveTypeEnum::URI:
    case PrimitiveTypeEnum::URL:
    case PrimitiveTypeEnum::ID:
    case PrimitiveTypeEnum::UUID:
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::BASE_64_BINARY:
        // TODO: add content decoding?
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::MARKDOWN:
        // TODO: markdown lib, maybe?
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    case PrimitiveTypeEnum::SAMPLE_DATA_TYPE:
        echo require __DIR__ . '/primitive/string_type.php';
        break;

    default:
        throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
}
?>
    /**
     * @param bool \$returnSXE
     * @param null|\SimpleXMLElement \$sxe
     * @return string|\SimpleXMLElement
     */
    public function xmlSerialize($returnSXE = false, \SimpleXMLElement $sxe = null)
    {
        if (null === $sxe) {
            $sxe = new \SimpleXMLElement('<<?php echo $xmlName; ?> xmlns="<?php echo PHPFHIR_FHIR_XMLNS; ?>"></<?php echo $xmlName; ?>>');
        }
        $sxe->addAttribute('value', (string)$this);
        return $returnSXE ? $sxe : $sxe->saveXML();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }
}<?php return ob_get_clean();