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

$valueMatcher = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALUE_MATCHER);
$mockResource = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);
$mockElement = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_ELEMENT_TYPE);
$mockPrimitive = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $valueMatcher,
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

    private function coding(string $system, string $code): <?php echo $mockElement; ?>

    {
        return new <?php echo $mockElement; ?>(
            name: 'Coding',
            fields: $this->field('system', <?php echo $mockPrimitive; ?>::class, $this->prim($system))
                + $this->field('code', <?php echo $mockPrimitive; ?>::class, $this->prim($code)),
        );
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

    public function testScalarMatchesPrimitive()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('a'), 'a'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('a'), 'b'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches(null, 'a'));
    }

    public function testNumericComparisonIgnoresRepresentation()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('1.0'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('2'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('x'), 1));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('-0.50'), -0.5));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('+7'), 7));
        // beyond float precision: a float cast would call these equal
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('9007199254740993'), 9007199254740992));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('0.1000000000000000001'), 0.1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1e0'), 1));
    }

    public function testPatternIsSubsetMatch()
    {
        $coding = $this->coding('http://sys', 'c1');
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($coding, ['system' => 'http://sys']));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($coding, ['system' => 'http://sys', 'code' => 'c1']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($coding, ['system' => 'http://sys', 'code' => 'c2']));
    }

    public function testPatternMatchesAnyValueOfCollectionField()
    {
        $cc = $this->codeableConcept([$this->coding('s1', 'a'), $this->coding('s2', 'b')]);
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($cc, ['coding' => [['system' => 's2', 'code' => 'b']]]));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($cc, ['coding' => ['system' => 's1']]));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($cc, ['coding' => [['system' => 's1', 'code' => 'b']]]));
    }

    public function testListRequiresEveryEntry()
    {
        $cc = $this->codeableConcept([$this->coding('s1', 'a'), $this->coding('s2', 'b')]);
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($cc, ['coding' => [['code' => 'a'], ['code' => 'b']]]));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($cc, ['coding' => [['code' => 'a'], ['code' => 'z']]]));
    }

    public function testComplexValueDoesNotMatchScalar()
    {
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->coding('http://sys', 'c1'), 'c1'));
    }
}
<?php return ob_get_clean();
