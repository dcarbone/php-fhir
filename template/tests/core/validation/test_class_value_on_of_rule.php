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
$oneOfRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_ONE_OF);

$testCoreFiles = $config->getCoreTestFiles();
$mockResource = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);
$mockPrimitive = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $mockResource,
    $mockPrimitive,
    $oneOfRule,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testNoErrorWhenValueOnlyAllowed()
    {
        $type = new <?php echo $mockPrimitive; ?>('string-primitive', 'one');
        $rule = new <?php echo $oneOfRule; ?>();
        $res = $rule->assert($type, 'value', ['one'], $type->getValue());
        $this->assertTrue($res->ok(), $res->error ?? 'Result should be OK, but is not and no error was defined.');
        $this->assertEquals('', $res->error ?? '');
    }

    public function testNoErrorWhenValueAllowed()
    {
        $type = new <?php echo $mockPrimitive; ?>('string-primitive', 'one');
        $rule = new <?php echo $oneOfRule; ?>();
        $res = $rule->assert($type, 'value', ['one', 'two'], $type->getValue());
        $this->assertTrue($res->ok(), $res->error ?? 'Result should be OK, but is not and no error was defined.');
        $this->assertEquals('', $res->error ?? '');
    }

    public function testNoErrorWhemValueEmpty()
    {
        $type = new <?php echo $mockPrimitive; ?>('string-primitive', null);
        $rule = new <?php echo $oneOfRule; ?>();
        $res = $rule->assert($type, 'value', ['one'], $type->getValue());
        $this->assertTrue($res->ok(), $res->error ?? 'Result should be OK, but is not and no error was defined.');
        $this->assertEquals('', $res->error ?? '');
    }

    public function testNoErrorWhenConstraintEmpty()
    {
        $type = new <?php echo $mockPrimitive; ?>('string-primitive', null);
        $rule = new <?php echo $oneOfRule; ?>();
        $res = $rule->assert($type, 'value', [], $type->getValue());
        $this->assertTrue($res->ok(), $res->error ?? 'Result should be OK, but is not and no error was defined.');
        $this->assertEquals('', $res->error ?? '');
    }
}
<?php return ob_get_clean();
