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

$imports = $coreFile->getImports();
$imports->addCoreFileImportsByName(
    PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG,
    PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG,
    PHPFHIR_CLASSNAME_VERSION_CONFIG,
    PHPFHIR_CLIENT_CLASSNAME_CONFIG,
);

$coreFiles = $config->getCoreFiles();

$versionConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_VERSION_CONFIG);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testCanConstructWithoutParams()
    {
        $vc = new <?php echo $versionConfigClass; ?>();
        $uc = $vc->getUnserializeConfig();
        $this->assertInstanceOf(<?php echo $unserializeConfigClass; ?>::class, $uc);
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $uc->getLibxmlOpts());
        $this->assertEquals(512, $uc->getJSONDecodeMaxDepth());
        $sc = $vc->getSerializeConfig();
        $this->assertInstanceOf(<?php echo $serializeConfigClass; ?>::class, $sc);
        $this->assertFalse($sc->getOverrideSourceXMLNS());
        $this->assertNull($sc->getRootXMLNS());
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $sc->getXHTMLLibxmlOpts());
    }

    public function testCanConstructWithValidMapParams()
    {
        $vc = new <?php echo $versionConfigClass; ?>(
            unserializeConfig: ['libxmlOpts' => 456, 'jsonDecodeMaxDepth' => 789],
            serializeConfig: ['overrideSourceXMLNS' => true, 'rootXMLNS' => 'urn:foo:bar', 'xhtmlLibxmlOpts' => 123],
        );
        $uc = $vc->getUnserializeConfig();
        $this->assertInstanceOf(<?php echo $unserializeConfigClass; ?>::class, $uc);
        $this->assertEquals(456, $uc->getLibxmlOpts());
        $this->assertEquals(789, $uc->getJSONDecodeMaxDepth());
        $sc = $vc->getSerializeConfig();
        $this->assertInstanceOf(<?php echo $serializeConfigClass; ?>::class, $sc);
        $this->assertTrue($sc->getOverrideSourceXMLNS());
        $this->assertEquals('urn:foo:bar', $sc->getRootXMLNS());
        $this->assertEquals(123, $sc->getXHTMLLibxmlOpts());
    }

    public function testCanConstructWithValidObjectParams()
    {
        $vc = new <?php echo $versionConfigClass; ?>(
            unserializeConfig: new <?php echo $unserializeConfigClass; ?>(),
            serializeConfig: new <?php echo $serializeConfigClass; ?>(),
        );
        $uc = $vc->getUnserializeConfig();
        $this->assertInstanceOf(<?php echo $unserializeConfigClass; ?>::class, $uc);
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $uc->getLibxmlOpts());
        $this->assertEquals(512, $uc->getJSONDecodeMaxDepth());
        $sc = $vc->getSerializeConfig();
        $this->assertInstanceOf(<?php echo $serializeConfigClass; ?>::class, $sc);
        $this->assertFalse($sc->getOverrideSourceXMLNS());
        $this->assertNull($sc->getRootXMLNS());
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $sc->getXHTMLLibxmlOpts());
    }

    public function testClientConfigIsNullByDefault(): void
    {
        $vc = new <?php echo $versionConfigClass; ?>();
        $this->assertNull($vc->getClientConfig());
    }

    public function testCanConstructWithClientConfigString(): void
    {
        $vc = new <?php echo $versionConfigClass; ?>(clientConfig: 'https://fhir.example.com');
        $cc = $vc->getClientConfig();
        $this->assertInstanceOf(<?php echo $clientConfigClass; ?>::class, $cc);
        $this->assertEquals('https://fhir.example.com', $cc->getAddress());
    }

    public function testCanConstructWithClientConfigArray(): void
    {
        $vc = new <?php echo $versionConfigClass; ?>(clientConfig: [
            'address'             => 'https://fhir.example.com',
            'defaultQueryParams'  => ['_pretty' => 'true'],
            'parseResponseHeaders' => false,
        ]);
        $cc = $vc->getClientConfig();
        $this->assertInstanceOf(<?php echo $clientConfigClass; ?>::class, $cc);
        $this->assertEquals('https://fhir.example.com', $cc->getAddress());
        $this->assertEquals(['_pretty' => 'true'], $cc->getDefaultQueryParams());
        $this->assertFalse($cc->getParseResponseHeaders());
    }

    public function testCanConstructWithClientConfigObject(): void
    {
        $obj = new <?php echo $clientConfigClass; ?>(address: 'https://fhir.example.com');
        $vc  = new <?php echo $versionConfigClass; ?>(clientConfig: $obj);
        $this->assertSame($obj, $vc->getClientConfig());
    }

    public function testSetClientConfigNull(): void
    {
        $vc = new <?php echo $versionConfigClass; ?>(clientConfig: 'https://fhir.example.com');
        $this->assertNotNull($vc->getClientConfig());
        $vc->setClientConfig(null);
        $this->assertNull($vc->getClientConfig());
    }

    // Fix: $_clientConfig must be null by default (typed-property initialisation)
    public function testClientConfigPropertyIsNullWithoutEverCallingSet(): void
    {
        // Construct, then immediately read — must not throw a typed-property error
        $vc = new <?php echo $versionConfigClass; ?>();
        $this->assertNull($vc->getClientConfig());

        // Also verify that a second fresh instance is independent
        $vc2 = new <?php echo $versionConfigClass; ?>();
        $this->assertNull($vc2->getClientConfig());
    }

    // Fix: array branch must throw when 'address' key is absent
    public function testSetClientConfigArrayWithoutAddressThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $vc = new <?php echo $versionConfigClass; ?>();
        $vc->setClientConfig(['defaultQueryParams' => ['_pretty' => 'true']]);
    }

    // Fix: array branch must throw when 'address' key is an empty string
    public function testSetClientConfigArrayWithEmptyAddressThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $vc = new <?php echo $versionConfigClass; ?>();
        $vc->setClientConfig(['address' => '   ']);
    }

    // Fix: constructing via clientConfig array without address must throw
    public function testConstructWithClientConfigArrayWithoutAddressThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new <?php echo $versionConfigClass; ?>(clientConfig: ['parseResponseHeaders' => false]);
    }
}
<?php return ob_get_clean();
