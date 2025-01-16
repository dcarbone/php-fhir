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
    PHPFHIR_ENCODING_CLASSNAME_XML_WRITER,
);

$coreFiles = $config->getCoreFiles();

$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo PHPFHIR_TEST_ENCODING_CLASSSNAME_XML_WRITER; ?> extends TestCase
{
    public function testCanConstructWithDefaultConfig()
    {
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertFalse($xw->isOpen());
        $this->assertNull($xw->getWriteDestination());
        $this->assertFalse($xw->isDocStarted());
        $this->assertFalse($xw->isRootOpen());
    }

    public function testCanGetMemoryWriteDestination()
    {
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openMemory());
        $this->assertEquals('memory', $xw->getWriteDestination());
    }

    public function testCanGetUriWriteDestination()
    {
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openUri('php://memory'));
        $this->assertEquals('php://memory', $xw->getWriteDestination());
    }

    public function testCannotOpenMemoryTwice()
    {
        $this->expectException(\LogicException::class);
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openMemory());
        $xw->openMemory();
    }

    public function testCannotOpenUriTwice()
    {
        $this->expectException(\LogicException::class);
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openUri('php://memory'));
        $xw->openUri('php://memory');
    }

    public function testCannotOpenMixedTwice()
    {
        $this->expectException(\LogicException::class);
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openMemory());
        $xw->openUri('php://memory');
    }

    public function testCanStartDocument()
    {
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertFalse($xw->isDocStarted());
        $this->assertTrue($xw->openMemory());
        $this->assertTrue($xw->startDocument());
        $this->assertTrue($xw->isDocStarted());
    }

    public function testCannotStartDocumentTwice()
    {
        $this->expectException(\LogicException::class);
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openMemory());
        $this->assertTrue($xw->startDocument());
        $xw->startDocument();
    }

    public function testCanOpenRootNode()
    {
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openMemory());
        $this->assertTrue($xw->startDocument());
        $this->assertFalse($xw->isRootOpen());
        $this->assertTrue($xw->openRootNode('root', null));
        $this->assertTrue($xw->isRootOpen());
    }

    public function testCannotOpenRootNodeTwice()
    {
        $this->expectException(\LogicException::class);
        $sc = new <?php echo $serializeConfigClass->getEntityName(); ?>();
        $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($sc);
        $this->assertTrue($xw->openMemory());
        $this->assertTrue($xw->startDocument());
        $this->assertFalse($xw->isRootOpen());
        $this->assertTrue($xw->openRootNode('root', null));
        $this->assertTrue($xw->isRootOpen());
        $this->assertTrue($xw->openRootNode('root', null));
    }
}
<?php return ob_get_clean();
