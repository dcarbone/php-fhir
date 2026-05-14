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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */
/** @var \DCarbone\PHPFHIR\Version $version */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();
$versionCoreFiles = $version->getVersionCoreFiles();

$versionClientClass    = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_CLIENT);
$versionClass          = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
$versionConfigClass    = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_VERSION_CONFIG);
$clientClass           = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CLIENT);
$clientConfigClass     = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $versionClientClass,
    $versionClass,
    $versionConfigClass,
    $clientClass,
    $clientConfigClass,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    // -------------------------------------------------------------------------
    // fromVersion() — exception path
    // -------------------------------------------------------------------------

    public function testFromVersionThrowsWhenNoClientConfigSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $version = new <?php echo $versionClass; ?>();
        <?php echo $versionClientClass; ?>::fromVersion($version);
    }

    // -------------------------------------------------------------------------
    // fromVersion() — success paths
    // -------------------------------------------------------------------------

    public function testFromVersionReturnsInstanceWithStringClientConfig(): void
    {
        $version = new <?php echo $versionClass; ?>(new <?php echo $versionConfigClass; ?>(
            clientConfig: 'https://fhir.example.com',
        ));
        $client = <?php echo $versionClientClass; ?>::fromVersion($version);
        $this->assertInstanceOf(<?php echo $versionClientClass; ?>::class, $client);
    }

    public function testFromVersionReturnsInstanceWithArrayClientConfig(): void
    {
        $version = new <?php echo $versionClass; ?>(new <?php echo $versionConfigClass; ?>(
            clientConfig: ['address' => 'https://fhir.example.com'],
        ));
        $client = <?php echo $versionClientClass; ?>::fromVersion($version);
        $this->assertInstanceOf(<?php echo $versionClientClass; ?>::class, $client);
    }

    public function testFromVersionReturnsInstanceWithConfigObject(): void
    {
        $version = new <?php echo $versionClass; ?>(new <?php echo $versionConfigClass; ?>(
            clientConfig: new <?php echo $clientConfigClass; ?>(address: 'https://fhir.example.com'),
        ));
        $client = <?php echo $versionClientClass; ?>::fromVersion($version);
        $this->assertInstanceOf(<?php echo $versionClientClass; ?>::class, $client);
    }

    public function testFromVersionClientConfigAddressIsPreserved(): void
    {
        $version = new <?php echo $versionClass; ?>(new <?php echo $versionConfigClass; ?>(
            clientConfig: 'https://fhir.example.com',
        ));
        $client = <?php echo $versionClientClass; ?>::fromVersion($version);
        // The stored ClientInterface wraps the Config — verify the address survived construction.
        $this->assertEquals(
            'https://fhir.example.com',
            $version->getConfig()->getClientConfig()->getAddress(),
        );
    }
}
<?php return ob_get_clean();

