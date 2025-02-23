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

$valuePatternMatchRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);
$valueMinLengthRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MIN_LENGTH);

$resourceIDTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_ID_TYPE);

$mockPrimitiveContainerClass = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_PRIMITIVE_CONTAINER_TYPE);
$mockStringpPrimitiveClass = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);

$imports->addCoreFileImports(
    $valuePatternMatchRule,
    $valueMinLengthRule,

    $resourceIDTypeInterface,

    $mockPrimitiveContainerClass,
    $mockStringpPrimitiveClass,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> extends <?php echo $mockPrimitiveContainerClass; ?> implements <?php echo $resourceIDTypeInterface; ?>

{

    public function __construct(string|<?php echo $mockStringpPrimitiveClass; ?> $value,
                                array $fields = [],
                                array $validationRuleMap = [],
                                array $fhirComments = [],
                                string $versionName = 'mock',
                                string $semanticVersion = 'v99.99.99')
    {
        parent::__construct(
            name: 'id',
            fields: [
                'value' => [
                    'class' => <?php echo $mockStringpPrimitiveClass; ?>::class,
                    'value' => $value,
                ],
            ] + $fields,
            validationRuleMap: [
                'value' => [
                    <?php echo $valuePatternMatchRule; ?>::NAME => '/^[A-Za-z0-9\\-\\.]{1,64}$/',
                    <?php echo $valueMinLengthRule; ?>::NAME => 1,
                ],
            ] + $validationRuleMap,
            fhirComments: $fhirComments,
            versionName: $versionName,
            semanticVersion: $semanticVersion,
        );
    }
}
<?php return ob_get_clean();
