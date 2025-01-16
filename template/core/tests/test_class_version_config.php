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
);

$coreFiles = $config->getCoreFiles();

$versionConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_VERSION_CONFIG);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo PHPFHIR_TEST_CLASSNAME_VERSION_CONFIG; ?> extends TestCase
{
    public function testCanConstructWithoutParams()
    {
        $vc = new <?php echo $versionConfigClass->getEntityName(); ?>();
        $uc = $vc->getUnserializeConfig();
        $this->assertInstanceOf(<?php echo $unserializeConfigClass->getEntityName(); ?>::class, $uc);
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $uc->getLibxmlOpts());
        $this->assertEquals(512, $uc->getJSONDecodeMaxDepth());
        $sc = $vc->getSerializeConfig();
        $this->assertInstanceOf(<?php echo $serializeConfigClass->getEntityName(); ?>::class, $sc);
        $this->assertFalse($sc->getOverrideSourceXMLNS());
        $this->assertNull($sc->getRootXMLNS());
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $sc->getXHTMLLibxmlOpts());
    }

    public function testCanConstructWithValidMapParams()
    {
        $vc = new <?php echo $versionConfigClass->getEntityName(); ?>(
            unserializeConfig: ['libxmlOpts' => 456, 'jsonDecodeMaxDepth' => 789],
            serializeConfig: ['overrideSourceXMLNS' => true, 'rootXMLNS' => 'urn:foo:bar', 'xhtmlLibxmlOpts' => 123],
        );
        $uc = $vc->getUnserializeConfig();
        $this->assertInstanceOf(<?php echo $unserializeConfigClass->getEntityName(); ?>::class, $uc);
        $this->assertEquals(456, $uc->getLibxmlOpts());
        $this->assertEquals(789, $uc->getJSONDecodeMaxDepth());
        $sc = $vc->getSerializeConfig();
        $this->assertInstanceOf(<?php echo $serializeConfigClass->getEntityName(); ?>::class, $sc);
        $this->assertTrue($sc->getOverrideSourceXMLNS());
        $this->assertEquals('urn:foo:bar', $sc->getRootXMLNS());
        $this->assertEquals(123, $sc->getXHTMLLibxmlOpts());
    }

    public function testCanConstructWithValidObjectParams()
    {
        $vc = new <?php echo $versionConfigClass->getEntityName(); ?>(
            unserializeConfig: new <?php echo $unserializeConfigClass->getEntityName(); ?>(),
            serializeConfig: new <?php echo $serializeConfigClass->getEntityName(); ?>(),
        );
        $uc = $vc->getUnserializeConfig();
        $this->assertInstanceOf(<?php echo $unserializeConfigClass->getEntityName(); ?>::class, $uc);
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $uc->getLibxmlOpts());
        $this->assertEquals(512, $uc->getJSONDecodeMaxDepth());
        $sc = $vc->getSerializeConfig();
        $this->assertInstanceOf(<?php echo $serializeConfigClass->getEntityName(); ?>::class, $sc);
        $this->assertFalse($sc->getOverrideSourceXMLNS());
        $this->assertNull($sc->getRootXMLNS());
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $sc->getXHTMLLibxmlOpts());
    }
}
<?php return ob_get_clean();
