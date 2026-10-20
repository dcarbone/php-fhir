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
    }

    public function testExponentNotationIsExpanded()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('1e0'), 1));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('1.5E+2'), 150));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('15e-1'), 1.5));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('-2.50e1'), -25));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1e1'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1e'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('e1'), 10));
    }

    public function testVerySmallFloats()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0.0000005'), 0.0000005));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0.00000050'), 5.0E-7));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('5e-7'), 0.0000005));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('-0.0000005'), -0.0000005));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0.000000000000000000001'), 1e-21));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('0.000005'), 0.0000005));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('0'), 0.0000005));
    }

    public function testVeryLargeFloats()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('10000000000000000000000000'), 1e25));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('1.5e25'), 1.5e25));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('10000000000000000000000001'), 1e25));
    }

    public function testFloatsNeedingFullPrecision()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0.30000000000000004'), 0.1 + 0.2));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('0.3'), 0.1 + 0.2));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0.1'), 0.1));
    }

    public function testFloatComparisonIgnoresIniPrecision()
    {
        $precision = ini_get('precision');
        $serializePrecision = ini_get('serialize_precision');
        ini_set('precision', '3');
        ini_set('serialize_precision', '17');
        try {
            $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0.1'), 0.1));
            $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('123456.789'), 123456.789));
        } finally {
            ini_set('precision', $precision);
            ini_set('serialize_precision', $serializePrecision);
        }
    }

    public function testZerosSignsAndLeadingZeros()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('-0.0'), 0));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('+0'), -0.0));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('0e5'), 0));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('007'), 7));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('00.50'), 0.5));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('-1'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('.5'), 0.5));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('5.'), 5));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim(' 5'), 5));
    }

    public function testNonFiniteAndOutOfRangeNumbersNeverMatch()
    {
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1'), INF));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1'), NAN));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1e1001'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1e99999999999999999999'), 1));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->prim('1e1000'), 1));
    }

    public function testRawScalars()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches(5, 5));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches(5, 5.0));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches(0.0000005, 5e-7));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches(true, true));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches('true', true));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches(1, true));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches(5, '5'));
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

    public function testMatchesEachRequiresEveryOccurrence()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matchesEach([$this->coding('s', 'a'), $this->coding('s', 'b')], ['system' => 's']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matchesEach([$this->coding('s', 'a'), $this->coding('t', 'b')], ['system' => 's']));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matchesEach($this->coding('s', 'a'), ['system' => 's']));
        // within a value, a list entry still only has to match some entry of the field.
        $cc = $this->codeableConcept([$this->coding('s1', 'a'), $this->coding('s2', 'b')]);
        $this->assertTrue(<?php echo $valueMatcher; ?>::matchesEach([$cc], ['coding' => [['code' => 'b']]]));
    }

    public function testEqualsIsExact()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals($this->prim('a'), 'a'));
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals($this->coding('s', 'c'), ['system' => 's', 'code' => 'c']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->coding('s', 'c', 'C'), ['system' => 's', 'code' => 'c']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->coding('s', 'c'), ['system' => 's', 'code' => 'c', 'display' => 'C']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->coding('s', 'c'), 'c'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals(null, ['code' => 'c']));
    }

    public function testEqualsComparesListsInFull()
    {
        $fixed = ['coding' => [['system' => 's1', 'code' => 'a'], ['system' => 's2', 'code' => 'b']]];
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals($this->codeableConcept([$this->coding('s1', 'a'), $this->coding('s2', 'b')]), $fixed));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->codeableConcept([$this->coding('s2', 'b'), $this->coding('s1', 'a')]), $fixed));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->codeableConcept([$this->coding('s1', 'a')]), $fixed));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->codeableConcept([$this->coding('s1', 'a'), $this->coding('s2', 'b'), $this->coding('s3', 'c')]), $fixed));
    }

    public function testEqualsRequiresEveryOccurrence()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals([$this->coding('s', 'c'), $this->coding('s', 'c')], ['system' => 's', 'code' => 'c']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals([$this->coding('s', 'c'), $this->coding('s', 'd')], ['system' => 's', 'code' => 'c']));
    }

    public function testNullsAndEmptyCollections()
    {
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches([], 'a'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches([null], 'a'));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches([null, $this->prim('a')], 'a'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matchesEach([null, $this->prim('a')], 'a'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals([null, $this->prim('a')], 'a'));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matchesEach(null, 'a'));
        // an absent value is the rules' concern, not the matcher's.
        $this->assertTrue(<?php echo $valueMatcher; ?>::matchesEach([], 'a'));
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals([], 'a'));
    }

    public function testPatternListOnScalarField()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches([$this->prim('a'), $this->prim('b')], ['a', 'b']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches([$this->prim('a')], ['a', 'b']));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('a'), ['a']));
    }

    public function testEmptyPatternMatchesAnyValue()
    {
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->coding('s', 'c'), []));
        $this->assertTrue(<?php echo $valueMatcher; ?>::matches($this->prim('a'), []));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($this->coding('s', 'c'), []));
    }

    public function testEqualsAcceptsElidedSingletonList()
    {
        $cc = new <?php echo $mockElement; ?>(
            name: 'CodeableConcept',
            fields: $this->field('coding', <?php echo $mockElement; ?>::class, $this->coding('s', 'c')),
        );
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals($cc, ['coding' => [['system' => 's', 'code' => 'c']]]));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($cc, ['coding' => [['system' => 's', 'code' => 'c'], ['system' => 's', 'code' => 'd']]]));
    }

    public function testEqualsIgnoresComments()
    {
        $coding = new <?php echo $mockElement; ?>(
            name: 'Coding',
            fields: $this->field('fhir_comments', <?php echo $mockPrimitive; ?>::class, $this->prim('note'))
                + $this->field('code', <?php echo $mockPrimitive; ?>::class, $this->prim('c')),
        );
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals($coding, ['code' => 'c']));
    }

    public function testEqualsComparesNestedNumbersByValue()
    {
        $quantity = new <?php echo $mockElement; ?>(
            name: 'Quantity',
            fields: $this->field('value', <?php echo $mockPrimitive; ?>::class, $this->prim('0.00000050'))
                + $this->field('unit', <?php echo $mockPrimitive; ?>::class, $this->prim('mg')),
        );
        $this->assertTrue(<?php echo $valueMatcher; ?>::equals($quantity, ['value' => 5e-7, 'unit' => 'mg']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($quantity, ['value' => 5e-6, 'unit' => 'mg']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($quantity, ['value' => ['nested' => 1], 'unit' => 'mg']));
        $this->assertFalse(<?php echo $valueMatcher; ?>::equals($quantity, ['value' => [5e-7], 'unit' => 'mg']));
    }

    public function testComplexValueDoesNotMatchScalar()
    {
        $this->assertFalse(<?php echo $valueMatcher; ?>::matches($this->coding('http://sys', 'c1'), 'c1'));
    }
}
<?php return ob_get_clean();
