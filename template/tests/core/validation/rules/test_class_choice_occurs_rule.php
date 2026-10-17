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

$rule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_CHOICE_OCCURS);
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
            fields: $this->field('coding', <?php echo $mockElement; ?>::class, $codings, true),
        );
    }

    private function resource(array $fields): <?php echo $mockResource; ?>

    {
        // every variant is a field of the type, whether or not it is set.
        $fields += $this->field('valueString', <?php echo $mockPrimitive; ?>::class, null) + $this->field('valueCode', <?php echo $mockPrimitive; ?>::class, null);
        return new <?php echo $mockResource; ?>(name: 'mock', fields: $fields);
    }

    private function constraint(array $extra = []): array
    {
        return ['fields' => ['valueString', 'valueCode']] + $extra;
    }

    public function testNoErrorWithExactlyOneVariant()
    {
        $type = $this->resource($this->field('valueString', <?php echo $mockPrimitive; ?>::class, $this->prim('x')));
        $this->assertNull((new <?php echo $rule; ?>())->assert($type, 'value[x]', $this->constraint(['min' => 1, 'max' => 1]), null));
    }

    public function testErrorWhenNoVariantSetAndMinimumRequired()
    {
        $type = $this->resource($this->field('other', <?php echo $mockPrimitive; ?>::class, $this->prim('x')));
        $this->assertNotNull((new <?php echo $rule; ?>())->assert($type, 'value[x]', $this->constraint(['min' => 1]), null));
    }

    public function testErrorWhenTooManyVariantsSet()
    {
        $type = $this->resource(
            $this->field('valueString', <?php echo $mockPrimitive; ?>::class, $this->prim('x'))
            + $this->field('valueCode', <?php echo $mockPrimitive; ?>::class, $this->prim('y')),
        );
        $this->assertNotNull((new <?php echo $rule; ?>())->assert($type, 'value[x]', $this->constraint(['max' => 1]), null));
    }

    public function testErrorWhenDisallowedVariantSet()
    {
        $type = $this->resource($this->field('valueCode', <?php echo $mockPrimitive; ?>::class, $this->prim('y')));
        $rule = new <?php echo $rule; ?>();
        $this->assertNull($rule->assert($type, 'value[x]', $this->constraint(['allowed' => ['valueCode']]), null));
        $err = $rule->assert($type, 'value[x]', $this->constraint(['allowed' => ['valueString']]), null);
        $this->assertNotNull($err);
        $this->assertStringContainsString('valueCode', $err);
    }
}
<?php return ob_get_clean();
