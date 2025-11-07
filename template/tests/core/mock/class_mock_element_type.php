<?php declare(strict_types=1);

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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$elementTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE);
$commentContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_COMMENT_CONTAINER);
$commentContainerTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_TRAIT_COMMENT_CONTAINER);

$typeValidationTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_TRAIT_TYPE_VALIDATIONS);

$jsonSerializableOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_JSON_SERIALIZATION_OPTIONS);
$xmlSerializationOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_XML_SERIALIZATION_OPTIONS);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$unserializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$serializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

$mockAbstractTypeClass = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_ABSTRACT_MOCK_TYPE);
$mockTypeFieldsTrait = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_TRAIT_MOCK_TYPE_FIELDS);

$imports->addCoreFileImports(
    $elementTypeInterface,
    $commentContainerInterface,
    $commentContainerTrait,

    $typeValidationTrait,

    $jsonSerializableOptionsTrait,
    $xmlSerializationOptionsTrait,
    $xmlWriterClass,
    $unserializeConfig,
    $serializeConfig,

    $mockAbstractTypeClass,
    $mockTypeFieldsTrait,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> extends <?php echo $mockAbstractTypeClass; ?> implements <?php echo $elementTypeInterface; ?>, <?php echo $commentContainerInterface; ?>, \Iterator

{
    use <?php echo $typeValidationTrait; ?>,
        <?php echo $jsonSerializableOptionsTrait; ?>,
        <?php echo $xmlSerializationOptionsTrait; ?>,
        <?php echo $commentContainerTrait; ?>,
        <?php echo $mockTypeFieldsTrait; ?>;

    private const _FHIR_VALIDATION_RULES = [];

    private array $_valueXMLLocations = [];

    public function __construct(string $name,
                                array $fields = [],
                                array $validationRuleMap = [],
                                array $fhirComments = [],
                                string $versionName = self::DEFAULT_MOCK_VERSION_NAME,
                                string $semanticVersion = self::DEFAULT_MOCK_SEMANTIC_VERSION)
    {
        parent::__construct($name, $versionName, $semanticVersion);

        $this->_setFHIRComments($fhirComments);
        foreach($validationRuleMap as $field => $rules) {
            $this->_setFieldValidationRules($field, $rules);
        }
        $this->_processFields($fields);
    }

    public static function xmlUnserialize(\SimpleXMLElement $element,
                                          <?php echo $unserializeConfig; ?> $config,
                                          null|<?php echo $elementTypeInterface; ?> $type = null): self
    {
        throw new \BadMethodCallException('xmlUnserialize not yet implemented');
    }

    public function xmlSerialize(<?php echo $xmlWriterClass; ?> $xw,
                                 <?php echo $serializeConfig; ?> $config): void
    {
        $this->_xmlSerialize($xw, $config);
    }

    public static function jsonUnserialize(\stdClass $decoded,
                                           <?php echo $unserializeConfig; ?> $config,
                                           null|<?php echo $elementTypeInterface; ?> $type = null): self
    {
        throw new \BadMethodCallException('jsonUnserialize not yet implemented');
    }

    public function __toString(): string
    {
        return $this->_name;
    }
}
<?php return ob_get_clean();
