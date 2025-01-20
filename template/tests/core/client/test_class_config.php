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
    PHPFHIR_CLIENT_CLASSNAME_CONFIG,
    PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT,
);

$coreFiles = $config->getCoreFiles();

$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);
$responseFormatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo PHPFHIR_TEST_CLIENT_CLASSNAME_CONFIG; ?> extends TestCase
{
    public function testCanConstructWithOnlyAddress()
    {
        $c = new <?php echo $clientConfigClass->getEntityName(); ?>('http://example.com');
        $this->assertEquals('http://example.com', $c->getAddress());
        $this->assertNull($c->getDefaultFormat());
        $this->assertEmpty($c->getQueryParams());
        $this->assertEmpty($c->getCurlOpts());
        $this->assertFalse($c->getParseResponseHeaders());
    }

    public function testCanConstructWithAllParams()
    {
        $c = new <?php echo $clientConfigClass->getEntityName(); ?>(
            address: 'http://example.com',
            defaultFormat: <?php echo $responseFormatEnum->getEntityName(); ?>::JSON,
            queryParams: ['foo' => 'bar'],
            curlOpts: ['bar' => 'baz'],
            parseResponseHeaders: true
        );
        $this->assertEquals('http://example.com', $c->getAddress());
        $this->assertEquals(<?php echo $responseFormatEnum->getEntityName(); ?>::JSON, $c->getDefaultFormat());
        $this->assertEquals(['foo' => 'bar'], $c->getQueryParams());
        $this->assertEquals(['bar' => 'baz'], $c->getCurlOpts());
        $this->assertTrue($c->getParseResponseHeaders());
    }
}
<?php return ob_get_clean();
