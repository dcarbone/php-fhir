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
$clientConfigClass     = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $versionClientClass,
    $versionClass,
    $versionConfigClass,
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
    // Only meaningful when no default client config was baked in at generation
    // time; if GENERATED_CLIENT_CONFIG is non-empty, new Version() already has
    // a client config and fromVersion() will not throw.
    // -------------------------------------------------------------------------

    public function testFromVersionThrowsWhenNoClientConfigSet(): void
    {
        if ([] !== <?php echo $versionClass; ?>::GENERATED_CLIENT_CONFIG) {
            $this->markTestSkipped(
                'GENERATED_CLIENT_CONFIG is non-empty for this version; '
                . 'new Version() always has a client config so this exception path cannot be triggered.',
            );
        }
        $this->expectException(\InvalidArgumentException::class);
        $version = new <?php echo $versionClass; ?>();
        <?php echo $versionClientClass; ?>::fromVersion($version);
    }

    // -------------------------------------------------------------------------
    // GENERATED_CLIENT_CONFIG propagation path
    // Skipped unless a clientConfig was configured at generation time.
    // -------------------------------------------------------------------------

    public function testGeneratedClientConfigPropagatesIntoVersionConstructor(): void
    {
        if ([] === <?php echo $versionClass; ?>::GENERATED_CLIENT_CONFIG) {
            $this->markTestSkipped(
                'GENERATED_CLIENT_CONFIG is empty for this version; '
                . 'configure a default clientConfig during generation to activate this test.',
            );
        }
        // new Version() with no args must merge GENERATED_CLIENT_CONFIG and expose the client config.
        $version = new <?php echo $versionClass; ?>();
        $clientConfig = $version->getConfig()->getClientConfig();
        $this->assertNotNull($clientConfig, 'getClientConfig() must return a Config when GENERATED_CLIENT_CONFIG is non-empty.');
        $this->assertEquals(
            <?php echo $versionClass; ?>::GENERATED_CLIENT_CONFIG['address'],
            $clientConfig->getAddress(),
            'The address baked into GENERATED_CLIENT_CONFIG must survive the Version constructor.',
        );
    }

    public function testFromVersionSucceedsWhenGeneratedClientConfigIsPresent(): void
    {
        if ([] === <?php echo $versionClass; ?>::GENERATED_CLIENT_CONFIG) {
            $this->markTestSkipped(
                'GENERATED_CLIENT_CONFIG is empty for this version; '
                . 'configure a default clientConfig during generation to activate this test.',
            );
        }
        // fromVersion() must build a client from the propagated config without throwing.
        $version = new <?php echo $versionClass; ?>();
        $client = <?php echo $versionClientClass; ?>::fromVersion($version);
        $this->assertInstanceOf(<?php echo $versionClientClass; ?>::class, $client);
    }

    // -------------------------------------------------------------------------
    // fromVersion() — explicit success paths (always run)
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
        <?php echo $versionClientClass; ?>::fromVersion($version);
        $this->assertEquals(
            'https://fhir.example.com',
            $version->getConfig()->getClientConfig()->getAddress(),
        );
    }
}
<?php return ob_get_clean();

