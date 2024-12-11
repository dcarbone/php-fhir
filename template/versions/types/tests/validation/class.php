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
use DCarbone\PHPFHIR\Version\SourceMetadata;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
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

$testNS = $type->getFullyQualifiedTestNamespace(TestType::VALIDATION, false);
$testClassname = $type->getTestClassName();
$typeNS = $type->getFullyQualifiedClassName(false);
$typeClassname = $type->getClassName();

ob_start();

echo "<?php\n\n";

echo "namespace {$testNS};\n\n";

echo SourceMetadata::getFullPHPFHIRCopyrightComment();
?>


use <?php echo $bundleType->getFullyQualifiedClassName(false); ?>;
use <?php echo $type->getFullyQualifiedClassName(false); ?>;
use <?php echo $config->getFullyQualifiedName(false); ?>\<?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?>;
use <?php echo $config->getFullyQualifiedName(false); ?>\<?php echo PHPFHIR_VERSION_ENUM_TYPE; ?>;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * Class <?php echo $testClassname; ?>

 * @package \<?php echo $testNS; ?>

 */
class <?php echo $testClassname; ?> extends TestCase
{
    /** @var array */
    private const IGNORE_ERRS = [
        'Unable to provide support for code system',
        ' minimum required =',
        ' Unable to resolve resource',
        'Identifier.system must be an absolute reference',
        ' Unknown Code System ',
        ' URL value ',
        ' None of the codes provided are in the value set ',
        'and a code is required from this value set',
        'fhir_comments',
        'None of the codings provided are in the value set',
        ' is not valid in the value set ',
        'this may not be a problem',
        'An expression or a reference must be provided',
        ' should be usable as an identifier for the module by machine processing applications such as code generation',
        ' Unknown code ',
        ' Wrong Display Name ',
        'If a code for the unit is present, the system SHALL also be present',
    ];

    /** @var <?php echo $config->getFullyQualifiedName(true); ?>\<?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?> */
    private <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?> $client;

    /** @var array */
    private array $_fetchedResources = [];

    protected function setUp(): void
    {
        $this->client = new PHPFHIRDebugClient('<?php echo rtrim($config->getTestEndpoint(), '/'); ?>');
    }

    /**
     * @var string $filename
     * @return array
     */
    protected function _runFHIRValidationJAR(string $filename): array
    {
        $output = [];
        $code = -1;
        $cmd = sprintf(
            'java -jar %s %s -version <?php echo SourceMetadata::getFHIRVersion(true); ?>',
            PHPFHIR_FHIR_VALIDATION_JAR,
            $filename
        );

        exec($cmd, $output, $code);

        $onlyWarn = false;
        if (0 !== $code) {
            foreach($output as $line) {
                foreach(self::IGNORE_ERRS as $ignoreMe) {
                    if (str_contains($line, $ignoreMe)) {
                        $onlyWarn = true;
                        break;
                    }
                }
            }
        }

        return [$code, $output, $onlyWarn];
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
        $rc = $this->client->get(sprintf('/%s', <?php echo PHPFHIR_VERSION_ENUM_TYPE; ?>::<?php echo $type->getConstName(false); ?>->value), ['_count' => '1', '_format' => $format]);
        $this->assertEmpty($rc->err, sprintf('curl error seen: %s', $rc->err));
        $this->assertEquals(200, $rc->code, 'Expected 200 OK');
        $this->assertIsString($rc->resp);
        $this->_fetchedResources[$format] = $rc->resp;
        $fname = sprintf('%s%s<?php echo $type->getFHIRName(); ?>-<?php echo SourceMetadata::getFHIRVersion(false); ?>-source.%s', PHPFHIR_OUTPUT_TMP_DIR, DIRECTORY_SEPARATOR, $format);
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

    public function testFHIRValidationXML(): void
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
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned xml: %s)',
                $sourceXML
            ));
        }
<?php if ($bundleEntryProperty->isCollection()) : ?>
        $resource = $entry[0]->getResource();
<?php else: ?>
        $resource = $entry->getResource();
<?php endif; ?>
        $fname = PHPFHIR_OUTPUT_TMP_DIR . DIRECTORY_SEPARATOR . $resource->_getFhirTypeName() . '-<?php echo SourceMetadata::getFHIRVersion(false); ?>.xml';
        file_put_contents($fname, $bundle->xmlSerialize()->ownerDocument->saveXML());
        $this->assertFileExists($fname);

        [$code, $output, $onlyWarn] = $this->_runFHIRValidationJAR($fname);

        if ($onlyWarn) {
            $this->markTestSkipped(sprintf(
                'FHIR validation failed with nonsense code error: %s',
                implode("\n", $output)
            ));
        } else {
            $this->assertEquals(
                0,
                $code,
                sprintf(
                    "Expected exit code 0, saw %d:\n%s",
                    $code,
                    implode("\n", $output)
                )
            );
        }
    }

    public function testFHIRValidationJSON()
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
<?php if ($bundleEntryProperty->isCollection()) : ?>
        $resource = $entry[0]->getResource();
<?php else: ?>
        $resource = $entry->getResource();
<?php endif; ?>
        $fname = PHPFHIR_OUTPUT_TMP_DIR . DIRECTORY_SEPARATOR . $resource->_getFhirTypeName() . '-<?php echo SourceMetadata::getFHIRVersion(false); ?>.json';
        file_put_contents($fname, json_encode($bundle));
        $this->assertFileExists($fname);

        [$code, $output, $onlyWarn] = $this->_runFHIRValidationJAR($fname);

        if ($onlyWarn) {
            $this->markTestSkipped(sprintf(
                'FHIR validation failed with nonsense code error: %s',
                implode("\n", $output)
            ));
        } else {
            $this->assertEquals(
                0,
                $code,
                sprintf(
                    "Expected exit code 0, saw %d:\n%s",
                    $code,
                    implode("\n", $output)
                )
            );
        }
    }
}
<?php return ob_get_clean();