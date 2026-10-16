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

$rule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_CODE_BINDING);
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
            fields: $this->field('coding', <?php echo $mockElement; ?>::class, $codings, true)
                // the mock answers to every getter, so the probes for a code / system have to find nothing set.
                + $this->field('code', <?php echo $mockPrimitive; ?>::class, null)
                + $this->field('system', <?php echo $mockPrimitive; ?>::class, null),
        );
    }

    private function resource(array $fields): <?php echo $mockResource; ?>

    {
        return new <?php echo $mockResource; ?>(name: 'mock', fields: $fields);
    }

    private function codes(): array
    {
        return [['http://sys', 'a'], [null, 'b']];
    }

    public function testNoErrorWhenValueIsNull()
    {
        $this->assertNull((new <?php echo $rule; ?>())->assert($this->resource([]), 'code', $this->codes(), null));
    }

    public function testPrimitiveCode()
    {
        $rule = new <?php echo $rule; ?>();
        $this->assertNull($rule->assert($this->resource([]), 'code', $this->codes(), $this->prim('b')));
        $this->assertNotNull($rule->assert($this->resource([]), 'code', $this->codes(), $this->prim('z')));
        // a bare code carries no system, so it matches on the code alone
        $this->assertNull($rule->assert($this->resource([]), 'code', $this->codes(), $this->prim('a')));
    }

    public function testCodingChecksSystemAndCode()
    {
        $rule = new <?php echo $rule; ?>();
        $this->assertNull($rule->assert($this->resource([]), 'code', $this->codes(), $this->coding('http://sys', 'a')));
        $this->assertNotNull($rule->assert($this->resource([]), 'code', $this->codes(), $this->coding('http://other', 'a')));
        $this->assertNull($rule->assert($this->resource([]), 'code', $this->codes(), $this->coding('http://anything', 'b')));
    }

    public function testCodeableConceptNeedsOneAllowedCoding()
    {
        $rule = new <?php echo $rule; ?>();
        $ok = $this->codeableConcept([$this->coding('x', 'nope'), $this->coding('http://sys', 'a')]);
        $bad = $this->codeableConcept([$this->coding('x', 'nope')]);
        $this->assertNull($rule->assert($this->resource([]), 'code', $this->codes(), $ok));
        $this->assertNotNull($rule->assert($this->resource([]), 'code', $this->codes(), $bad));
    }

    public function testEveryElementOfCollectionMustBeAllowed()
    {
        $rule = new <?php echo $rule; ?>();
        $this->assertNull($rule->assert($this->resource([]), 'code', $this->codes(), [$this->prim('b'), $this->prim('b')]));
        $this->assertNotNull($rule->assert($this->resource([]), 'code', $this->codes(), [$this->prim('b'), $this->prim('z')]));
    }
}
<?php return ob_get_clean();
