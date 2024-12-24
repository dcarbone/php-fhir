<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TestTypeEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var string $testType */

$config = $version->getConfig();

$typeKind = $type->getKind();

$bundleType = $types->getBundleType();
$bundleEntryProperty = null;

// we can only perform integration tests on "Resource" types.
if (!$type->isResourceType()) {
    return;
}

// TODO(@dcarbone): find a more efficient way to do this...
if (null === $bundleType) {
    throw ExceptionUtils::createBundleTypeNotFoundException($type);
}

foreach($bundleType->getAllPropertiesIterator() as $prop) {
    if ($prop->getName() === 'entry') {
        $bundleEntryProperty = $prop;
        break;
    }
}

if (null === $bundleEntryProperty) {
    throw ExceptionUtils::createBundleEntryPropertyNotFoundException($type);
}

$testNS = $type->getFullyQualifiedTestNamespace(TestTypeEnum::INTEGRATION, false);
$testClassname = $type->getTestClassName();
$typeNS = $type->getFullyQualifiedClassName(false);
$typeClassName = $type->getClassName();

ob_start();

echo "<?php\n\n";

echo "namespace {$testNS};\n\n";

echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment();
?>


use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_API_CLIENT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_API_CLIENT_CONFIG); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_API_FORMAT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_RESPONSE_PARSER); ?>;
use <?php echo $bundleType->getFullyQualifiedClassName(false); ?>;
use <?php echo $type->getFullyQualifiedClassName(false); ?>;
use <?php echo $version->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_VERSION_API_CLIENT); ?>;
use <?php echo $version->getFullyQualifiedName(false, PHPFHIR_ENUM_VERSION_TYPE); ?>;
use <?php echo $version->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_VERSION); ?>;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * Class <?php echo $testClassname; ?>

 * @package \<?php echo $testNS; ?>

 */
class <?php echo $testClassname; ?> extends TestCase
{
    /** @var string */
    private string $_testEndpoint;

    /** @var <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION); ?> */
    private <?php echo PHPFHIR_CLASSNAME_VERSION; ?> $_version;

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_CONFIG); ?> */
    private <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?> $_clientConfig;

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT); ?> */
    private <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?> $_baseClient;

    /** @var <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION_API_CLIENT); ?> */
    private <?php echo PHPFHIR_CLASSNAME_VERSION_API_CLIENT; ?> $_client;

    /** @var array */
    private array $_fetchedResources = [];

    protected function setUp(): void
    {
        $endpoint = trim((string)getenv('<?php echo PHPFHIR_TEST_CONSTANT_INTEGRATION_ENDPOINT; ?>'));
        if ('' === $endpoint) {
            $this->markTestIncomplete('Environment variable <?php echo PHPFHIR_TEST_CONSTANT_INTEGRATION_ENDPOINT; ?> is not defined or empty');
        }
        $this->_testEndpoint = $endpoint;
        $this->_version = new <?php echo PHPFHIR_CLASSNAME_VERSION ?>();
        $this->_clientConfig = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG ?>(address: $endpoint);
        $this->_baseClient = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?>($this->_clientConfig);
        $this->_client = new <?php echo PHPFHIR_CLASSNAME_VERSION_API_CLIENT ?>(
            $this->_baseClient,
            $this->_version,
        );
    }

    /**
     * @param string $format Either xml or json
     * @return string
     * @throws \Exception
     */
    protected function fetchResourceBundle(string $format): string
    {
        if (isset($this->_fetchedResources[$format])) {
            return $this->_fetchedResources[$format];
        }
        $rc = $this->_client->readRaw(
            resourceType: <?php echo PHPFHIR_ENUM_VERSION_TYPE; ?>::<?php echo $type->getConstName(false); ?>,
            count: 5,
            format: <?php echo PHPFHIR_ENUM_API_FORMAT; ?>::from($format),
        );
        $this->assertEmpty($rc->err, sprintf('curl error seen: %s', $rc->err));
        if (404 === $rc->code) {
            $this->markTestSkipped(sprintf('Query "%s" returned no resources of type "%s"', $rc->url, <?php echo PHPFHIR_ENUM_VERSION_TYPE; ?>::<?php echo $type->getConstName(false); ?>->value));
        } else if (500 === $rc->code) {
            $this->markTestSkipped(sprintf('Query "%s" returned 500', $rc->url));
        } else {
            $this->assertEquals(200, $rc->code, 'Expected 200 OK');
        }
        $this->assertIsString($rc->resp);
        $this->_fetchedResources[$format] = $rc->resp;
        $dlDir = trim((string)getenv('<?php echo PHPFHIR_TEST_CONSTANT_RESOURCE_DOWNLOAD_DIR; ?>'));
        if ('' !== $dlDir) {
            $this->assertDirectoryExists($dlDir, sprintf('Configured test resource download directory "%s" does not exist.', $dlDir));
            $fname = sprintf('%s%s<?php echo $type->getFHIRName(); ?>-<?php echo $version->getSourceMetadata()->getFHIRVersion(false); ?>-source.%s', $dlDir, DIRECTORY_SEPARATOR, $format);
            file_put_contents($fname, $rc->resp);
            $this->assertFileExists($fname, sprintf('Failed to write fetched resource bundle to "%s"', $fname));
        }
        return $rc->resp;
    }

    /**
     * @param string $sourceJSON
     * @param bool $asArray
     * @return mixed
     */
    protected function decodeJson(string $sourceJSON, bool $asArray): mixed
    {
        $this->assertJson($sourceJSON);
        $decoded = json_decode($sourceJSON, $asArray);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->fail(sprintf(
                'Error decoded JSON: %s; Raw: %s',
                function_exists('json_last_error_msg') ? json_last_error_msg() : ('Code: '.json_last_error()),
                $sourceJSON
            ));
        }
        return $decoded;
    }

    /**
     * @throws \Exception
     */
    public function testXML(): void
    {
        $sourceXML = $this->fetchResourceBundle('xml');
        try {
            $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize($sourceXML);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from XML: %s; Returned XML: %s',
                    $e->getMessage(),
                    $sourceXML
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $bundleType->getClassName(); ?>::class, $bundle);
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "%s" does not have any "<?php echo $type->getFHIRName(); ?>" entries to test against (returned xml: %s)',
                $this->_testEndpoint,
                $sourceXML
            ));
        }
<?php if ($bundleEntryProperty->isCollection()) : ?>
        foreach ($entry as $ent) {
<?php else: ?>
        foreach ([$entry] as $ent) {
<?php endif; ?>
            $resource = $ent->getResource();
            $resourceXmlWriter = $resource->xmlSerialize();
            $resourceXml = $resourceXmlWriter->outputMemory();
            try {
                $type = <?php echo $type->getClassName(); ?>::xmlUnserialize($resourceXml);
            } catch (\Exception $e) {
                throw new AssertionFailedError(
                    sprintf(
                        'Error building type "<?php echo $type->getFHIRName(); ?>" from XML: %s; XML: %s',
                        $e->getMessage(),
                        $resourceXml
                    ),
                    $e->getCode(),
                    $e
                );
            }
            $this->assertInstanceOf(<?php echo $type->getClassName(); ?>::class, $type);
            $typeXmlWriter = $type->xmlSerialize();
            $this->assertEquals($resourceXml, $typeXmlWriter->outputMemory());
            $bundleXmlWriter = $bundle->xmlSerialize();
            $this->assertXmlStringEqualsXmlString($sourceXML, $bundleXmlWriter->outputMemory());
        }
    }

    /**
     * @throws \Exception
     */
    public function testJSON(): void
    {
        $sourceJSON = $this->fetchResourceBundle('json');
        $decoded = $this->decodeJson($sourceJSON, true);
        try {
            $bundle = new <?php echo $bundleType->getClassName(); ?>($decoded);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from JSON: %s; Returned JSON: %s',
                    $e->getMessage(),
                    $sourceJSON
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $bundleType->getClassName(); ?>::class, $bundle);
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "%s" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
                $this->_testEndpoint,
                $sourceJSON
            ));
        }

        $reEncoded = json_encode($bundle);
        try {
            $this->assertEquals($decoded, $this->decodeJson($reEncoded, true));
        } catch (\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    "json_encode output of \"<?php echo $type->getClassName(); ?>\" does not match input: %s\nSource:\n%s\nRe-encoded:\n%s\n",
                    $e->getMessage(),
                    $sourceJSON,
                    $reEncoded
                ),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function testValidationXML(): void
    {
        $sourceXML = $this->fetchResourceBundle('xml');
        try {
            $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize($sourceXML);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from XML: %s; Returned XML: %s',
                    $e->getMessage(),
                    $sourceXML
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $bundleType->getClassName(); ?>::class, $bundle);
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "%s" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned XML: %s)',
                $this->_testEndpoint,
                $sourceXML
            ));
        }
        $errs = $bundle->_getValidationErrors();
        try {
            $this->assertCount(0, $errs);
        } catch (\Exception $e) {
            $this->markTestSkipped(sprintf('Validation errors seen: %s', json_encode($errs, JSON_PRETTY_PRINT)));
        }
    }

    /**
     * @throws \Exception
     */
    public function testValidationJSON(): void
    {
        $sourceJSON = $this->fetchResourceBundle('json');
        $decoded = $this->decodeJson($sourceJSON, true);
        try {
            $bundle = new <?php echo $bundleType->getClassName(); ?>($decoded);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from JSON: %s; Returned JSON: %s',
                    $e->getMessage(),
                    $sourceJSON
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $bundleType->getClassName(); ?>::class, $bundle);
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "%s" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
                $this->_testEndpoint,
                $sourceJSON
            ));
        }
        $errs = $bundle->_getValidationErrors();
        try {
            $this->assertCount(0, $errs);
        } catch (\Exception $e) {
            $this->markTestSkipped(sprintf('Validation errors seen: %s', json_encode($errs, JSON_PRETTY_PRINT)));
        }
    }

    /**
     * @throws \Exception
     */
    public function testResponseParserXML(): void
    {
        $sourceXML = $this->fetchResourceBundle('xml');
        try {
            $bundle = <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>::parse($this->_version, $sourceXML);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from XML: %s; Returned XML: %s',
                    $e->getMessage(),
                    $sourceXML
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $bundleType->getClassName(); ?>::class, $bundle);
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "%s" does not have any "<?php echo $type->getFHIRName(); ?>" entries to test against (returned xml: %s)',
                $this->_testEndpoint,
                $sourceXML
            ));
        }
<?php if ($bundleEntryProperty->isCollection()) : ?>
        foreach ($entry as $ent) {
<?php else: ?>
        foreach ([$entry] as $ent) {
<?php endif; ?>
            $resource = $ent->getResource();
            $resourceXmlWriter = $resource->xmlSerialize();
            $resourceXml = $resourceXmlWriter->outputMemory();
            try {
                $type = <?php echo $type->getClassName(); ?>::xmlUnserialize($resourceXml);
            } catch (\Exception $e) {
                throw new AssertionFailedError(
                    sprintf(
                        'Error building type "<?php echo $type->getFHIRName(); ?>" from XML: %s; XML: %s',
                        $e->getMessage(),
                        $resourceXml
                    ),
                    $e->getCode(),
                    $e
                );
            }
            $this->assertInstanceOf(<?php echo $type->getClassName(); ?>::class, $type);
            $typeXmlWriter = $type->xmlSerialize();
            $this->assertEquals($resourceXml, $typeXmlWriter->outputMemory());
            $bundleXmlWriter = $bundle->xmlSerialize();
            $this->assertXmlStringEqualsXmlString($sourceXML, $bundleXmlWriter->outputMemory());
        }
    }

    /**
     * @throws \Exception
     */
    public function testResponseParserJSON(): void
    {
        $sourceJSON = $this->fetchResourceBundle('json');
        try {
            $bundle = <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>::parse($this->_version, $sourceJSON);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from JSON: %s; Returned JSON: %s',
                    $e->getMessage(),
                    $sourceJSON
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $bundleType->getClassName(); ?>::class, $bundle);
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "%s" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
                $this->_testEndpoint,
                $sourceJSON
            ));
        }

        $reEncoded = json_encode($bundle);
        try {
            $this->assertJsonStringEqualsJsonString($sourceJSON, $reEncoded);
        } catch (\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    "json_encode output of \"<?php echo $type->getClassName(); ?>\" does not match input: %s\nSource:\n%s\nRe-encoded:\n%s\n",
                    $e->getMessage(),
                    $sourceJSON,
                    $reEncoded
                ),
                $e->getCode(),
                $e
            );
        }
    }
}
<?php
return ob_get_clean();
