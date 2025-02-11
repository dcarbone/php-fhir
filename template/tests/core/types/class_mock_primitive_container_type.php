<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$primitiveInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$primitiveContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE);

$typeValidationTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_TRAIT_TYPE_VALIDATIONS);

$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);
$jsonSerializableOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_JSON_SERIALIZATION_OPTIONS);
$xmlSerializationOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_XML_SERIALIZATION_OPTIONS);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$serializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

$mockTypeFieldsTrait = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_TRAIT_MOCK_TYPE_FIELDS);
$mockElementTypeClass = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_ELEMENT_TYPE);

$imports->addCoreFileImports(
    $primitiveInterface,
    $primitiveContainerInterface,

    $typeValidationTrait,

    $valueXMLLocationEnum,
    $jsonSerializableOptionsTrait,
    $xmlSerializationOptionsTrait,
    $xmlWriterClass,
    $serializeConfig,

    $mockTypeFieldsTrait,
    $mockElementTypeClass,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> extends <?php echo $mockElementTypeClass; ?> implements <?php echo $primitiveContainerInterface; ?>

{
    use <?php echo $typeValidationTrait; ?>,
        <?php echo $jsonSerializableOptionsTrait; ?>,
        <?php echo $xmlSerializationOptionsTrait; ?>,
        <?php echo $mockTypeFieldsTrait; ?>;


    private const _FHIR_VALIDATION_RULES = [];

    private array $_valueXMLLocations = [];

    public function __construct(string $name,
                                array $fields = [],
                                array $validationRuleMap = [],
                                array $fhirComments = [],
                                mixed $value = null)
    {
        if (!isset($fields['value'])
            || !isset($fields['value']['class'])
            || !is_a($fields['value']['class'], <?php echo $primitiveInterface; ?>::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Primitive container type "%s" must have a "value" field and it must be a primitive type.',
                $name,
            ));
        }
        if (null !== $value) {
            $fields['value']['value'] = $value;
        }
        parent::__construct($name, $fields, $validationRuleMap, $fhirComments);
    }

    public function _nonValueFieldDefined(): bool
    {
        foreach($this->_fields as $field => $def) {
            if ('value' !== $field && isset($def['value']) && [] !== $def['value']) {
                return true;
            }
        }
        return false;
    }

    public function xmlSerialize(<?php echo $xmlWriterClass; ?> $xw,
                                 <?php echo $serializeConfig; ?> $config,
                                 null|<?php echo $valueXMLLocationEnum; ?> $valueLocation = null): void
    {
        $this->_xmlSerialize($xw, $config, $valueLocation);
    }

    public function _getValueAsString(): string
    {
        return (string)$this->getValue();
    }
}
<?php return ob_get_clean();
