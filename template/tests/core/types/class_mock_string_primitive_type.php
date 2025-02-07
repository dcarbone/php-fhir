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

$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$typeValidationTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_TRAIT_TYPE_VALIDATIONS);
$jsonSerializableOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_JSON_SERIALIZATION_OPTIONS);
$xmlSerializationOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_XML_SERIALIZATION_OPTIONS);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $primitiveTypeInterface,
    $typeValidationTrait,
    $jsonSerializableOptionsTrait,
    $xmlSerializationOptionsTrait,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $primitiveTypeInterface; ?>

{
    use <?php echo $typeValidationTrait; ?>,
        <?php echo $jsonSerializableOptionsTrait; ?>,
        <?php echo $xmlSerializationOptionsTrait; ?>;

    private const _FHIR_VALIDATION_RULES = [];

    private string $_name;

    protected string $value;

    public function __construct(string $name = 'mock-string-primitive',
                                null|string $value = null,
                                array $validationRuleMap = [])
    {
        $this->_name = $name;
        $this->setValue($value);
        foreach($validationRuleMap as $field => $rules) {
            $this->_setFieldValidationRules($field, $validationRuleMap);
        }
    }

    public function _getFHIRTypeName(): string
    {
        return $this->_name;
    }

    public function getValue(): null|string
    {
        return $this->value ?? null;
    }

    public function setValue(null|string $value): self
    {
        if (null === $value) {
            unset($this->value);
        } else {
            $this->value = $value;
        }
        return $this;
    }

    public function _getValueAsString(): string
    {
        return (string)$this->getValue();
    }

    public function jsonSerialize(): string
    {
        return $this->value ?? '';
    }

    public function __toString(): string
    {
        return $this->_getValueAsString();
    }
}
<?php return ob_get_clean();
