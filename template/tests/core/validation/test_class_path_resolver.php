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

$pathResolver = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_PATH_RESOLVER);
$mockPrimitive = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);
$mockElement = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_ELEMENT_TYPE);
$mockResource = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $pathResolver,
    $mockPrimitive,
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
    private function buildResource(): <?php echo $mockResource; ?>

    {
        $contacts = [];
        foreach ([['a', 'b'], ['c']] as $codes) {
            $values = [];
            foreach ($codes as $code) {
                $values[] = new <?php echo $mockPrimitive; ?>(value: $code);
            }
            $contacts[] = new <?php echo $mockElement; ?>(
                name: 'contact',
                fields: [
                    'code' => [
                        'class' => <?php echo $mockPrimitive; ?>::class,
                        'collection' => true,
                        'value' => $values,
                    ],
                ],
            );
        }
        return new <?php echo $mockResource; ?>(
            name: 'mock',
            fields: [
                'contact' => [
                    'class' => <?php echo $mockElement; ?>::class,
                    'collection' => true,
                    'value' => $contacts,
                ],
            ],
        );
    }

    public function testIsPath()
    {
        $this->assertFalse(<?php echo $pathResolver; ?>::isPath('contact'));
        $this->assertTrue(<?php echo $pathResolver; ?>::isPath('contact.code'));
    }

    public function testSingleSegmentResolvesToRoot()
    {
        $type = $this->buildResource();
        $resolved = <?php echo $pathResolver; ?>::resolveParents($type, 'contact');
        $this->assertCount(1, $resolved);
        $this->assertSame($type, $resolved[0]['type']);
        $this->assertSame('contact', $resolved[0]['field']);
        $this->assertSame('contact', $resolved[0]['path']);
    }

    public function testCollectionIsExpandedWithIndexes()
    {
        $type = $this->buildResource();
        $resolved = <?php echo $pathResolver; ?>::resolveParents($type, 'contact.code');
        $this->assertCount(2, $resolved);
        $this->assertSame('contact.0.code', $resolved[0]['path']);
        $this->assertSame('contact.1.code', $resolved[1]['path']);
        $this->assertSame('code', $resolved[0]['field']);
        $this->assertSame($type->getContact()[0], $resolved[0]['type']);
        $this->assertSame($type->getContact()[1], $resolved[1]['type']);
    }

    public function testMultiLevelCollectionsExpand()
    {
        $type = $this->buildResource();
        $resolved = <?php echo $pathResolver; ?>::resolveParents($type, 'contact.code.value');
        $this->assertCount(3, $resolved);
        $this->assertSame('contact.0.code.0.value', $resolved[0]['path']);
        $this->assertSame('contact.0.code.1.value', $resolved[1]['path']);
        $this->assertSame('contact.1.code.0.value', $resolved[2]['path']);
    }

    public function testUnsetIntermediateResolvesToNothing()
    {
        $type = $this->buildResource();
        $empty = new <?php echo $mockResource; ?>(name: 'mock', fields: ['contact' => ['class' => <?php echo $mockElement; ?>::class, 'collection' => true]]);
        $this->assertSame([], <?php echo $pathResolver; ?>::resolveParents($empty, 'contact.code'));
        $unset = new <?php echo $mockResource; ?>(name: 'mock', fields: ['code' => ['class' => <?php echo $mockPrimitive; ?>::class, 'collection' => true]]);
        $this->assertSame([], <?php echo $pathResolver; ?>::resolveParents($unset, 'code.value'));
    }
}
<?php return ob_get_clean();
