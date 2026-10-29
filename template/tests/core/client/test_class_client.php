<?php declare(strict_types=1);

/*
 * Copyright 2025-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$clientClientClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CLIENT);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);
$requestClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_REQUEST);
$responseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);
$httpMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);
$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $clientClientClass,
    $clientConfigClass,
    $requestClass,
    $responseClass,
    $httpMethodEnum,
    $formatEnum,
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

    /**
     * Invokes the protected curl option builder.  ReflectionMethod::setAccessible() is a no-op since PHP 8.1
     * and deprecated in 8.3, so it is not called.
     */
    private function _buildCurlOpts(<?php echo $clientClientClass; ?> $cl,
                                    <?php echo $requestClass; ?> $req,
                                    bool $parseResponseHeaders = false): array
    {
        $format = $req->format ?? $cl->getConfig()->getDefaultFormat();
        $rc = new <?php echo $responseClass; ?>($req->method, 'http://example.com' . $req->path, $format);
        $m = new \ReflectionMethod($cl, '_buildCurlOpts');
        return $m->invoke($cl, $req, $rc, $format, $parseResponseHeaders);
    }

    public function testCurlOptsIntegerKeysArePreserved()
    {
        $c = new <?php echo $clientConfigClass; ?>(
            address: 'http://example.com',
            curlOpts: [
                CURLOPT_TIMEOUT => 17,
                CURLOPT_SSL_VERIFYPEER => false,
            ],
        );
        $cl = new <?php echo $clientClientClass; ?>($c);
        $req = new <?php echo $requestClass; ?>(<?php echo $httpMethodEnum; ?>::GET, '/Patient');

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertArrayHasKey(CURLOPT_TIMEOUT, $opts);
        $this->assertSame(17, $opts[CURLOPT_TIMEOUT]);
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $opts);
        $this->assertFalse($opts[CURLOPT_SSL_VERIFYPEER]);

        // array_merge would have renumbered the integer CURLOPT_* keys to 0, 1, ...
        foreach (array_keys($opts) as $k) {
            $this->assertIsInt($k);
            $this->assertGreaterThan(0, $k, sprintf('Found renumbered curl option key %d', $k));
        }
    }

    public function testBaseCurlOptsAreApplied()
    {
        $cl = new <?php echo $clientClientClass; ?>('http://example.com');
        $req = new <?php echo $requestClass; ?>(<?php echo $httpMethodEnum; ?>::GET, '/Patient');

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertTrue($opts[CURLOPT_RETURNTRANSFER]);
        $this->assertTrue($opts[CURLOPT_FOLLOWLOCATION]);
        $this->assertArrayHasKey(CURLOPT_USERAGENT, $opts);
        $this->assertStringStartsWith('php-fhir client', $opts[CURLOPT_USERAGENT]);
    }

    public function testConfigCurlOptsOverrideBaseCurlOpts()
    {
        $c = new <?php echo $clientConfigClass; ?>(
            address: 'http://example.com',
            curlOpts: [
                CURLOPT_USERAGENT => 'custom-agent/1.0',
                CURLOPT_FOLLOWLOCATION => false,
            ],
        );
        $cl = new <?php echo $clientClientClass; ?>($c);
        $req = new <?php echo $requestClass; ?>(<?php echo $httpMethodEnum; ?>::GET, '/Patient');

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertSame('custom-agent/1.0', $opts[CURLOPT_USERAGENT]);
        $this->assertFalse($opts[CURLOPT_FOLLOWLOCATION]);
        $this->assertTrue($opts[CURLOPT_RETURNTRANSFER]);
    }

    public function testRequestCurlOptsAreApplied()
    {
        $cl = new <?php echo $clientClientClass; ?>('http://example.com');
        $req = new <?php echo $requestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::GET,
            path: '/Patient',
            clientOptions: [
                CURLOPT_TIMEOUT => 3,
                CURLOPT_USERAGENT => 'request-agent/2.0',
            ],
        );

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertSame(3, $opts[CURLOPT_TIMEOUT]);
        $this->assertSame('request-agent/2.0', $opts[CURLOPT_USERAGENT]);
    }

    public function testRequestCurlOptsOverrideConfigCurlOpts()
    {
        $c = new <?php echo $clientConfigClass; ?>(
            address: 'http://example.com',
            curlOpts: [
                CURLOPT_TIMEOUT => 17,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT => 'config-agent/1.0',
            ],
        );
        $cl = new <?php echo $clientClientClass; ?>($c);
        $req = new <?php echo $requestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::GET,
            path: '/Patient',
            clientOptions: [
                CURLOPT_TIMEOUT => 3,
            ],
        );

        $opts = $this->_buildCurlOpts($cl, $req);

        // request wins
        $this->assertSame(3, $opts[CURLOPT_TIMEOUT]);
        // config values not overridden by request are retained
        $this->assertSame(5, $opts[CURLOPT_CONNECTTIMEOUT]);
        $this->assertSame('config-agent/1.0', $opts[CURLOPT_USERAGENT]);
    }

    public function testConfigAndRequestHttpHeadersAreCombined()
    {
        $c = new <?php echo $clientConfigClass; ?>(
            address: 'http://example.com',
            curlOpts: [
                CURLOPT_HTTPHEADER => ['Authorization: Bearer abc123'],
            ],
        );
        $cl = new <?php echo $clientClientClass; ?>($c);
        $req = new <?php echo $requestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::GET,
            path: '/Patient',
            clientOptions: [
                CURLOPT_HTTPHEADER => ['X-Request-ID: xyz'],
            ],
        );

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertContains('Authorization: Bearer abc123', $opts[CURLOPT_HTTPHEADER]);
        $this->assertContains('X-Request-ID: xyz', $opts[CURLOPT_HTTPHEADER]);
        // headers list must remain a list (recursive replace would have clobbered index 0)
        $this->assertSame(array_values($opts[CURLOPT_HTTPHEADER]), $opts[CURLOPT_HTTPHEADER]);
    }

    public function testUserHttpHeadersDoNotDisplaceGeneratedHeaders()
    {
        $c = new <?php echo $clientConfigClass; ?>(
            address: 'http://example.com',
            curlOpts: [
                CURLOPT_HTTPHEADER => ['Authorization: Bearer abc123'],
            ],
        );
        $cl = new <?php echo $clientClientClass; ?>($c);
        $req = new <?php echo $requestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::POST,
            path: '/Patient',
            format: <?php echo $formatEnum; ?>::JSON,
        );

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertSame('POST', $opts[CURLOPT_CUSTOMREQUEST]);
        $this->assertContains('Authorization: Bearer abc123', $opts[CURLOPT_HTTPHEADER]);
        $this->assertContains('X-HTTP-Method-Override: POST', $opts[CURLOPT_HTTPHEADER]);
        $accept = array_values(array_filter($opts[CURLOPT_HTTPHEADER], fn(string $h) => str_starts_with($h, 'Accept:')));
        $this->assertCount(1, $accept);
        $this->assertStringContainsString('json', $accept[0]);
    }

    public function testHttpHeaderIsAlwaysAList()
    {
        $cl = new <?php echo $clientClientClass; ?>('http://example.com');
        $req = new <?php echo $requestClass; ?>(<?php echo $httpMethodEnum; ?>::GET, '/Patient');

        $opts = $this->_buildCurlOpts($cl, $req);

        $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $opts);
        $this->assertIsArray($opts[CURLOPT_HTTPHEADER]);
        $this->assertNotEmpty($opts[CURLOPT_HTTPHEADER]);
    }

    public function testParseResponseHeadersAddsHeaderOpts()
    {
        $cl = new <?php echo $clientClientClass; ?>('http://example.com');
        $req = new <?php echo $requestClass; ?>(<?php echo $httpMethodEnum; ?>::GET, '/Patient');

        $opts = $this->_buildCurlOpts($cl, $req, true);
        $this->assertSame(1, $opts[CURLOPT_HEADER]);
        $this->assertIsCallable($opts[CURLOPT_HEADERFUNCTION]);

        $opts = $this->_buildCurlOpts($cl, $req, false);
        $this->assertArrayNotHasKey(CURLOPT_HEADER, $opts);
        $this->assertArrayNotHasKey(CURLOPT_HEADERFUNCTION, $opts);
    }

    public function testMergedCurlOptsAreAcceptedByCurl()
    {
        $c = new <?php echo $clientConfigClass; ?>(
            address: 'http://example.com',
            curlOpts: [
                CURLOPT_TIMEOUT => 17,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer abc123'],
            ],
        );
        $cl = new <?php echo $clientClientClass; ?>($c);
        $req = new <?php echo $requestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::GET,
            path: '/Patient',
            clientOptions: [CURLOPT_CONNECTTIMEOUT => 2],
        );

        $opts = $this->_buildCurlOpts($cl, $req, true);

        $ch = curl_init('http://example.com/Patient');
        try {
            // with array_merge-renumbered keys, curl_setopt_array fails on option 0
            $this->assertTrue(curl_setopt_array($ch, $opts));
        } finally {
            curl_close($ch);
        }
    }
}
<?php return ob_get_clean();
