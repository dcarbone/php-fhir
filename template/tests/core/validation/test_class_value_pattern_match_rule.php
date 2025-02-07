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
$patternRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);

$testCoreFiles = $config->getCoreTestFiles();
$mockResource = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);
$mockPrimitive = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $mockResource,
    $mockPrimitive,
    $patternRule,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testNoErrorWithValidPatternAndValue()
    {
        $type = new <?php echo $mockPrimitive; ?>('string-primitive', 'the quick brown fox jumped over the lazy dog');
        $rule = new <?php echo $patternRule; ?>();
        $res = $rule->assert($type, 'value', '/^[a-z\s]+$/', $type->getValue());
        $this->assertTrue($res->ok(), $res->error ?? 'Result should be OK, but is not and no error was defined.');
    }

    public function testErrorWithValidPatternAndInvalidValue()
    {
        $type = new <?php echo $mockPrimitive; ?>('string-primitive', 'the quick brown fox jumped over the lazy dog');
        $rule = new <?php echo $patternRule; ?>();
        $res = $rule->assert($type, 'value', '/^[a-z]+$/', $type->getValue());
        $this->assertFalse($res->ok(), 'Rule should have produced error');
        $this->assertNotEquals('', $res->error);
    }

    /**
     * @see https://github.com/dcarbone/php-fhir/issues/150
     */
    public function testErrorWithValueOverflow()
    {
        $bigval = base64_encode(str_repeat('a', 12000));
        $type = new <?php echo $mockPrimitive; ?>('base64-primitive', $bigval);
        $rule = new <?php echo $patternRule; ?>();
        $res = $rule->assert($type, 'value', '/^(\\s*([0-9a-zA-Z\\+\\/=]){4}\\s*)+$/', $type->getValue());
        $this->assertFalse($res->ok(), 'Rule should have produced error');
        $this->assertNotEquals('', $res->error);
    }
}
<?php return ob_get_clean();
