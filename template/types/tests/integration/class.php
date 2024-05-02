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

use DCarbone\PHPFHIR\Enum\TestType;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var string $testType */

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

$testNS = $type->getFullyQualifiedTestNamespace(TestType::INTEGRATION, false);
$testClassname = $type->getTestClassName();
$typeNS = $type->getFullyQualifiedClassName(false);
$typeClassname = $type->getClassName();

ob_start();

echo "<?php\n\n";

echo "namespace {$testNS};\n\n";

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();
?>


use <?php echo $bundleType->getFullyQualifiedClassName(false); ?>;
use <?php echo $type->getFullyQualifiedClassName(false); ?>;
use <?php echo $config->getFullyQualifiedName(false); ?>\<?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?>;
use <?php echo $config->getFullyQualifiedName(false); ?>\<?php echo PHPFHIR_ENUM_TYPE; ?>;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * Class <?php echo $testClassname; ?>

 * @package \<?php echo $testNS; ?>

 */
class <?php echo $testClassname; ?> extends TestCase
{
    /** @var <?php echo $config->getFullyQualifiedName(true); ?>\<?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?> */
    private <?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?> $client;

    /** @var array */
    private array $_fetchedResources = [];

    protected function setUp(): void
    {
        $this->client = new PHPFHIRDebugClient('<?php echo rtrim($config->getTestEndpoint(), '/'); ?>');
    }

    /**
     * @param string $format Either xml or json
     * @return string
     */
    protected function fetchResource(string $format): string
    {
        if (isset($this->_fetchedResources[$format])) {
            return $this->_fetchedResources[$format];
        }
        $rc = $this->client->get(sprintf('/%s', <?php echo PHPFHIR_ENUM_TYPE; ?>::<?php echo $type->getConstName(false); ?>->value), ['_count' => '1', '_format' => $format]);
        $this->assertEmpty($rc->err, sprintf('curl error seen: %s', $rc->err));
        $this->assertEquals(200, $rc->code, 'Expected 200 OK');
        $this->assertIsString($rc->resp);
        $this->_fetchedResources[$format] = $rc->resp;
        $fname = sprintf('%s%s<?php echo $type->getFHIRName(); ?>-<?php echo CopyrightUtils::getFHIRVersion(false); ?>-source.%s', PHPFHIR_OUTPUT_TMP_DIR, DIRECTORY_SEPARATOR, $format);
        file_put_contents($fname, $rc->resp);
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

    public function testXML(): void
    {
        $sourceXML = $this->fetchResource('xml');
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
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any "<?php echo $type->getFHIRName(); ?>" entries to test against (returned xml: %s)',
                $sourceXML
            ));
        }
<?php if ($bundleEntryProperty->isCollection()) : ?>
        $this->assertCount(1, $entry);
        $resource = $entry[0]->getResource();
<?php else: ?>
        $resource = $entry->getResource();
<?php endif; ?>
        $resourceElement = $resource->xmlSerialize();
        $resourceXML = $resourceElement->saveXML();
        try {
            $type = <?php echo $type->getClassName(); ?>::xmlUnserialize($resourceXML);
        } catch (\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $type->getFHIRName(); ?>" from XML: %s; XML: %s',
                    $e->getMessage(),
                    $resourceXML
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf(<?php echo $type->getClassName(); ?>::class, $type);
        $typeElement = $type->xmlSerialize();
        $this->assertEquals($resourceXML, $typeElement->saveXML());
        $bundleElement = $bundle->xmlSerialize();
        $this->assertXmlStringEqualsXmlString($sourceXML, $bundleElement->saveXML());
    }

    public function testJSON(): void
    {
        $sourceJSON = $this->fetchResource('json');
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
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
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

    public function testValidationXML(): void
    {
        $sourceXML = $this->fetchResource('xml');
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
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned XML: %s)',
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

    public function testValidationJSON(): void
    {
        $sourceJSON = $this->fetchResource('json');
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
        $entry = $bundle->getEntry();
<?php if ($bundleEntryProperty->isCollection()) : ?>
        if (0 === count($entry)) {
<?php else : ?>
        if (null === $entry) {
<?php endif; ?>
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
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
}
<?php
return ob_get_clean();