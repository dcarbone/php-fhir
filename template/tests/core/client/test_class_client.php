<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$clientClientClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CLIENT);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $clientClientClass,
    $clientConfigClass,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo PHPFHIR_TEST_CLIENT_CLASSNAME_CLIENT; ?> extends TestCase
{
    public function testCanConstructWithOnlyAddress()
    {
        $cl = new <?php echo $clientClientClass; ?>('http://example.com');
        $this->assertEquals('http://example.com', $cl->getConfig()->getAddress());
    }

    public function testCanConstructWithConfig()
    {
        $c = new <?php echo $clientConfigClass; ?>('http://example.com');
        $cl = new <?php echo $clientClientClass; ?>($c);
        $this->assertEquals('http://example.com', $cl->getConfig()->getAddress());
    }
}
<?php return ob_get_clean();
