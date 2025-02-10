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
$imports = $coreFile->getImports();
$testCoreFiles = $config->getCoreTestFiles();

$valueOneOfRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_ONE_OF);
$minLenRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MIN_LENGTH);
$maxLenRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MAX_LENGTH);
$patternMatchRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);
$minOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MIN_OCCURS);
$maxOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MAX_OCCURS);

$mockPrimitive = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);
$mockPrimitiveContainer = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_PRIMITIVE_CONTAINER_TPYE);
$mockElement = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_ELEMENT_TYPE);
$mockResource = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);

$imports->addCoreFileImports(
    $valueOneOfRule,
    $minLenRule,
    $maxLenRule,
    $patternMatchRule,
    $minOccursRule,
    $maxOccursRule,

    $mockPrimitive,
    $mockPrimitiveContainer,
    $mockElement,
    $mockResource,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testCanSetValidationRules()
    {
        $valueRules = [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z-]+$/'];
        $type = new <?php echo $mockPrimitive; ?>();
        $type->_setFieldValidationRules('value', $valueRules);
        $rules = $type->_getCombinedValidationRules();
        $this->assertCount(1, $rules, var_export($rules, true));
        $this->assertArrayHasKey('value', $rules);
        $this->assertEquals($valueRules, $rules['value'] ?? []);
        $this->assertArrayHasKey(<?php echo $patternMatchRule; ?>::NAME, $rules['value'] ?? []);
    }

    function testCanValidateSimpleTypeNoErrors()
    {
        $type = new <?php echo $mockPrimitive; ?>(value: 'ye-p', validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z-]+$/']]);
        $errs = $type->_getValidationErrors();
        $this->assertCount(0, $errs, var_export($errs, true));
    }

    function testCanValidateSimpleTypeWithErrors()
    {
        $type = new <?php echo $mockPrimitive; ?>(value: 'NOPE.', validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z-]+$/']]);
        $errs = $type->_getValidationErrors();
        $this->assertCount(1, $errs, var_export($errs, true));
    }

    public function testCanValidateComplexTypeNoErrors()
    {
        $type = new <?php echo $mockResource; ?>(
            name: 'mock',
            fields: [
                'identifier' => [
                    'class' => <?php echo $mockPrimitiveContainer; ?>::class,
                    'value' => new <?php echo $mockPrimitiveContainer; ?>(
                        name: 'string',
                        fields: [
                            'value' => [
                                'class' => <?php echo $mockPrimitive; ?>::class,
                                'value' => new <?php echo $mockPrimitive; ?>(
                                    value: 'mock-1',
                                    validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z0-9-]+$/']],
                                ),
                            ],
                        ],
                    ),
                ],
            ],
        );
        $this->assertCount(1, $type->getIdentifier()->getValue()->_getCombinedValidationRules());
        $errs = $type->_getValidationErrors();
        $this->assertEmpty($errs, var_export($errs, true));
    }

    public function testCanValidateComplexTypeWithErrors()
    {
        $type = new <?php echo $mockResource; ?>(
            name: 'mock',
            fields: [
                'identifier' => [
                    'class' => <?php echo $mockPrimitiveContainer; ?>::class,
                    'value' => new <?php echo $mockPrimitiveContainer; ?>(
                        name: 'string',
                        fields: [
                            'value' => [
                                'class' => <?php echo $mockPrimitive; ?>::class,
                                'value' => new <?php echo $mockPrimitive; ?>(
                                    value: 'mock_1',
                                    validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z-]+$/']],
                                ),
                            ],
                        ],
                    ),
                ],
            ],
        );
        $this->assertCount(1, $type->getIdentifier()->getValue()->_getCombinedValidationRules());
        $errs = $type->_getValidationErrors();
        $this->assertCount(1, $errs, var_export($errs, true));
    }

    public function testCanValidateSimpleCollectionFieldNoErrors()
    {
        $type = new <?php echo $mockElement; ?>(
            name: 'mock',
            fields: [
                'code' => [
                    'class' => <?php echo $mockPrimitive; ?>::class,
                    'collection' => true,
                    'value' => [
                        new <?php echo $mockPrimitive; ?>(
                            value: 'mock-1',
                            validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z0-9-]+$/']],
                        ),
                        new <?php echo $mockPrimitive; ?>(
                            value: 'mock-2',
                            validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z0-9-]+$/']],
                        ),
                    ],
                ],
            ],
            validationRuleMap: [
                'code' => [
                    <?php echo $minOccursRule; ?>::NAME => 2,
                ],
            ],
        );
        $errs = $type->_getValidationErrors();
        $this->assertCount(0, $errs, var_export($errs, true));
    }

    public function testCanValidateSimpleCollectionFieldErrors()
    {
        $type = new <?php echo $mockElement; ?>(
            name: 'mock',
            fields: [
                'code' => [
                    'class' => <?php echo $mockPrimitive; ?>::class,
                    'collection' => true,
                    'value' => [
                        new <?php echo $mockPrimitive; ?>(
                            value: 'mock-1',
                            validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z0-9-]+$/']],
                        ),
                        new <?php echo $mockPrimitive; ?>(
                            value: 'mock-2',
                            validationRuleMap: ['value' => [<?php echo $patternMatchRule; ?>::NAME => '/^[a-z-]+$/']],
                        ),
                    ],
                ],
            ],
            validationRuleMap: [
                'code' => [
                    <?php echo $minOccursRule; ?>::NAME => 3,
                ],
            ],
        );
        $errs = $type->_getValidationErrors();
        $this->assertCount(2, $errs, var_export($errs, true));
    }
}
<?php return ob_get_clean();
