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

$coreFiles    = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports      = $coreFile->getImports();

$fhirVersion      = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$mockVersion      = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_VERSION);

$imports->addCoreFileImports(
    $fhirVersion,
    $versionInterface,
    $mockVersion,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testImplementsVersionInterface(): void
    {
        $this->assertInstanceOf(<?php echo $versionInterface; ?>::class, new <?php echo $mockVersion; ?>());
    }

    public function testGetName(): void
    {
        $this->assertSame(<?php echo $mockVersion; ?>::NAME, (new <?php echo $mockVersion; ?>())->getName());
    }

    public function testGetFHIRSemanticVersion(): void
    {
        $this->assertSame(<?php echo $mockVersion; ?>::FHIR_SEMANTIC_VERSION, (new <?php echo $mockVersion; ?>())->getFHIRSemanticVersion());
    }

    public function testGetFHIRShortVersion(): void
    {
        $this->assertSame(<?php echo $mockVersion; ?>::FHIR_SHORT_VERSION, (new <?php echo $mockVersion; ?>())->getFHIRShortVersion());
    }

    public function testGetFHIRVersionInteger(): void
    {
        $this->assertSame(<?php echo $mockVersion; ?>::FHIR_VERSION_INTEGER, (new <?php echo $mockVersion; ?>())->getFHIRVersionInteger());
    }

    public function testGetFHIRPreReleaseIsNullForGAMock(): void
    {
        $this->assertNull((new <?php echo $mockVersion; ?>())->getFHIRPreRelease());
    }

    public function testIsFHIRPreReleaseIsFalseForGAMock(): void
    {
        $this->assertFalse((new <?php echo $mockVersion; ?>())->isFHIRPreRelease());
    }

    public function testGetFHIRVersionReturnsFHIRVersionInstance(): void
    {
        $fv = <?php echo $mockVersion; ?>::getFHIRVersion();
        $this->assertInstanceOf(<?php echo $fhirVersion; ?>::class, $fv);
    }

    public function testGetFHIRVersionCarriesCorrectValues(): void
    {
        $fv = <?php echo $mockVersion; ?>::getFHIRVersion();
        $this->assertSame(<?php echo $mockVersion; ?>::NAME, $fv->getName());
        $this->assertSame(<?php echo $mockVersion; ?>::FHIR_SEMANTIC_VERSION, $fv->getFHIRSemanticVersion());
        $this->assertSame(<?php echo $mockVersion; ?>::FHIR_SHORT_VERSION, $fv->getFHIRShortVersion());
        $this->assertSame(<?php echo $mockVersion; ?>::FHIR_VERSION_INTEGER, $fv->getFHIRVersionInteger());
        $this->assertNull($fv->getFHIRPreRelease());
        $this->assertFalse($fv->isFHIRPreRelease());
    }

    public function testGetFHIRVersionIsSingleton(): void
    {
        $this->assertSame(<?php echo $mockVersion; ?>::getFHIRVersion(), <?php echo $mockVersion; ?>::getFHIRVersion());
    }
}
<?php return ob_get_clean();

