<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$rule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_FIXED_VALUE);
$mockResource = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);
$mockElement = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_ELEMENT_TYPE);
$mockPrimitive = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $rule,
    $mockResource,
    $mockElement,
    $mockPrimitive,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    private function prim(string $value): <?php echo $mockPrimitive; ?>

    {
        return new <?php echo $mockPrimitive; ?>(value: $value);
    }

    private function field(string $name, string $class, mixed $value, bool $collection = false): array
    {
        return [$name => ['class' => $class, 'collection' => $collection, 'value' => $value]];
    }

    private function coding(string $system, string $code, null|string $display = null): <?php echo $mockElement; ?>

    {
        $fields = $this->field('system', <?php echo $mockPrimitive; ?>::class, $this->prim($system))
            + $this->field('code', <?php echo $mockPrimitive; ?>::class, $this->prim($code));
        if (null !== $display) {
            $fields += $this->field('display', <?php echo $mockPrimitive; ?>::class, $this->prim($display));
        }
        return new <?php echo $mockElement; ?>(name: 'Coding', fields: $fields);
    }

    private function codeableConcept(array $codings): <?php echo $mockElement; ?>

    {
        return new <?php echo $mockElement; ?>(
            name: 'CodeableConcept',
            fields: $this->field('coding', <?php echo $mockElement; ?>::class, $codings, true),
        );
    }

    private function resource(array $fields): <?php echo $mockResource; ?>

    {
        return new <?php echo $mockResource; ?>(name: 'mock', fields: $fields);
    }

    public function testNoErrorWhenValueIsNull()
    {
        $type = $this->resource([]);
        $this->assertNull((new <?php echo $rule; ?>())->assert($type, 'status', 'final', null));
    }

    public function testNoErrorWhenFixedValueMatches()
    {
        $type = $this->resource([]);
        $this->assertNull((new <?php echo $rule; ?>())->assert($type, 'status', 'final', $this->prim('final')));
    }

    public function testErrorWhenFixedValueDiffers()
    {
        $type = $this->resource([]);
        $this->assertNotNull((new <?php echo $rule; ?>())->assert($type, 'status', 'final', $this->prim('draft')));
    }

    public function testComplexFixedValue()
    {
        $type = $this->resource([]);
        $rule = new <?php echo $rule; ?>();
        $this->assertNull($rule->assert($type, 'code', ['system' => 's', 'code' => 'c'], $this->coding('s', 'c')));
        $this->assertNotNull($rule->assert($type, 'code', ['system' => 's', 'code' => 'c'], $this->coding('s', 'd')));
    }

    public function testComplexFixedValueRejectsAdditionalFields()
    {
        $type = $this->resource([]);
        $rule = new <?php echo $rule; ?>();
        $this->assertNotNull($rule->assert($type, 'code', ['system' => 's', 'code' => 'c'], $this->coding('s', 'c', 'C')));
        $this->assertNull($rule->assert($type, 'code', ['system' => 's', 'code' => 'c', 'display' => 'C'], $this->coding('s', 'c', 'C')));
    }

    public function testComplexFixedValueRejectsAdditionalEntries()
    {
        $type = $this->resource([]);
        $rule = new <?php echo $rule; ?>();
        $fixed = ['coding' => [['system' => 's', 'code' => 'c']]];
        $this->assertNull($rule->assert($type, 'code', $fixed, $this->codeableConcept([$this->coding('s', 'c')])));
        $this->assertNotNull($rule->assert($type, 'code', $fixed, $this->codeableConcept([$this->coding('s', 'c'), $this->coding('s', 'd')])));
    }

    public function testEveryOccurrenceMustMatch()
    {
        $type = $this->resource([]);
        $rule = new <?php echo $rule; ?>();
        $this->assertNull($rule->assert($type, 'code', ['system' => 's', 'code' => 'c'], [$this->coding('s', 'c'), $this->coding('s', 'c')]));
        $this->assertNotNull($rule->assert($type, 'code', ['system' => 's', 'code' => 'c'], [$this->coding('s', 'c'), $this->coding('s', 'd')]));
        $this->assertNotNull($rule->assert($type, 'status', 'final', [$this->prim('final'), $this->prim('draft')]));
    }
}
<?php return ob_get_clean();
