<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$types = $version->getDefinition()->getTypes();

$bundleType = $types->getBundleType();

$coreFiles = $version->getConfig()->getCoreFiles();

$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);
$clientClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CLIENT);
$clientFormatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);
$unexpectedCodeException = $coreFiles->getCoreFileByEntityName(PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE);

$versionCoreFiles = $version->getVersionCoreFiles();

$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
$versionClientClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_CLIENT);
$versionTypeEnum = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_ENUM_VERSION_RESOURCE_TYPE);

$imports = new Imports($version->getConfig(), $type->getFullyQualifiedTestNamespace(false), $type->getTestClassName());
$imports->addVersionTypeImports($type);

if (!$type->isAbstract()
    && $type !== $bundleType
    && !$type->getKind()->isResourceContainer($version)
    && ($type->isResourceType() || $type->hasResourceTypeParent())) {
    $imports
        ->addCoreFileImportsByName(
            PHPFHIR_CLASSNAME_FHIR_VERSION,
            PHPFHIR_CLIENT_CLASSNAME_CONFIG,
            PHPFHIR_CLIENT_CLASSNAME_CLIENT,
            PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT,
            PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE,
        )
        ->addVersionCoreFileImportsByName(
            $version,
            PHPFHIR_VERSION_CLASSNAME_VERSION,
            PHPFHIR_VERSION_CLASSNAME_VERSION_CLIENT,
            PHPFHIR_VERSION_ENUM_VERSION_RESOURCE_TYPE,
        );

    // DSTU1 does not have a "Bundle" type.
    if (!$version->getSourceMetadata()->isDSTU1()) {
        $imports->addVersionTypeImports(
            $bundleType
        );
    }
}

$typeKind = $type->getKind();

ob_start();

echo '<?php /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */'; ?>


namespace <?php echo $type->getFullyQualifiedTestNamespace(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $type->getTestClassName(); ?> extends TestCase
{
<?php
if (!$type->isAbstract()
    && $type !== $bundleType
    && !$type->getKind()->isResourceContainer($version)
    && ($type->isResourceType() || $type->hasResourceTypeParent())) : ?>
    protected <?php echo $versionClass; ?> $_version;

    protected function setUp(): void
    {
        $this->_version = new <?php echo $versionClass; ?>();
    }

    protected function _getTestEndpoint(): string
    {
        return trim((string)getenv('<?php echo PHPFHIR_TEST_CONSTANT_SERVER_ADDR; ?>'));
    }

    protected function _getClient(): <?php echo $versionClientClass; ?>

    {
        $testEndpoint = $this->_getTestEndpoint();
        if ('' === $testEndpoint) {
            $this->markTestSkipped('Environment variable <?php echo PHPFHIR_TEST_CONSTANT_SERVER_ADDR; ?> is not defined or empty');
        }
        return new <?php echo $versionClientClass; ?>(
            new <?php echo $clientClass; ?>(
                new <?php echo $clientConfigClass; ?>(
                    address: $testEndpoint,
                ),
            ),
            $this->_version,
        );
    }

<?php endif; ?>
    public function testCanConstructTypeNoArgs()
    {
        $type = new <?php echo $type->getClassName(); ?>();
        $this->assertEquals('<?php echo $type->getFHIRName(); ?>', $type->_getFHIRTypeName());
    }
<?php if ($type->isPrimitiveType() || $type->hasPrimitiveTypeParent() || $type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) :
    $primitiveType = match(true) {
        ($type->isPrimitiveType() || $type->hasPrimitiveTypeParent()) => $type->getPrimitiveType(),
        $type->isPrimitiveContainer() => $type->getProperties()->getProperty('value')->getValueFHIRType()->getPrimitiveType(),
        $type->hasPrimitiveContainerParent() => $type->getParentProperty('value')->getValueFHIRType()->getPrimitiveType(),
    };

    // TODO: more different types of strvals...
    $strVals = match ($primitiveType) {
        PrimitiveTypeEnum::INTEGER, PrimitiveTypeEnum::POSITIVE_INTEGER, PrimitiveTypeEnum::INTEGER64 => ['10', '1,000'],
        PrimitiveTypeEnum::NEGATIVE_INTEGER => ['-10', '-1,000'],
        PrimitiveTypeEnum::DECIMAL => ['10.5', '1,000.3333'],
        PrimitiveTypeEnum::UNSIGNED_INTEGER => [(string)PHP_INT_MAX, '1,000'],
        PrimitiveTypeEnum::BOOLEAN => ['true'],
        default => ['randomstring'],
    };
?>
    public function testCanConstructWithString()
    {
<?php foreach($strVals as $strVal) : ?>
        $type = new <?php echo $type->getClassName(); ?>(value: '<?php echo $strVal; ?>');
        $this->assertEquals('<?php echo $strVal; ?>', $type->_getValueAsString());
        $this->assertEquals('<?php echo $strVal; ?>', (string)$type);
<?php endforeach; ?>
    }

    public function testCanSetValueFromString()
    {
<?php foreach($strVals as $strVal) : ?>
        $type = new <?php echo $type->getClassName(); ?>();
        $type->setValue('<?php echo $strVal; ?>');
        $this->assertEquals('<?php echo $strVal; ?>', $type->_getValueAsString());
        $this->assertEquals('<?php echo $strVal; ?>', (string)$type);
<?php endforeach; ?>
    }

    public function testCanGetTypeFHIRVersion()
    {
        $type = new <?php echo $type->getFullyQualifiedClassName(true); ?>();
        $this->assertEquals(<?php echo $versionClass->getFullyQualifiedName(true); ?>::getFHIRVersion(), $type->_getFHIRVersion());
    }
<?php if ($primitiveType->isOneOf(PrimitiveTypeEnum::DATE, PrimitiveTypeEnum::DATETIME, PrimitiveTypeEnum::INSTANT, PrimitiveTypeEnum::TIME)): ?>

    public function testCanSetValueWithDateTime()
    {
        $date = \DateTime::createFromFormat("Y-m-d\TH:i:sP", '2020-02-02T20:20:20+00:00');
        $type = new <?php echo $type->getClassName(); ?>(value: $date);
<?php if ($primitiveType === PrimitiveTypeEnum::TIME): ?>
        $this->assertEquals($date->format('H:i:s'), $type->_getValueAsString());
<?php elseif ($primitiveType === PrimitiveTypeEnum::DATE): ?>
        $this->assertEquals($date->format('Y-m-d'), $type->_getValueAsString());
<?php elseif ($primitiveType === PrimitiveTypeEnum::INSTANT): ?>
        $this->assertEquals($date->format('Y-m-d\TH:i:s\.uP'), $type->_getValueAsString());
<?php elseif ($primitiveType === PrimitiveTypeEnum::DATETIME): ?>
        $this->assertEquals($date->format('Y-m-d\TH:i:s\.uP'), $type->_getValueAsString());
<?php endif; ?>
    }
<?php endif;
elseif ($type->isResourceType() || $type->hasResourceTypeParent()) : ?>
    function testCanUnserializeExtendedFields()
    {
        $json = new \stdClass();
        $json->_id = new \stdClass();
        $json->_id->extension = new \stdClass();
        $type = <?php echo $type->getClassName() ?>::jsonUnserialize($json);
        $this->assertNotEmpty($type->getId()->getExtension());
    }

    public function testCanExecuteValidations()
    {
        $type = new <?php echo $type->getclassName(); ?>();
        $errs = $type->_getValidationErrors();
        $this->assertIsArray($errs);
    }

    public function testCanJsonUnmarshalWithCorrectResourceType()
    {
        $dec = new \stdClass();
        $dec-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?> = '<?php echo $type->getFHIRName(); ?>';
        $resource = <?php echo $type->getClassName(); ?>::jsonUnserialize(decoded: $dec);
        $this->assertInstanceOf(<?php echo $type->getclassname(); ?>::class, $resource);
    }

    public function testCanJsonUnmarshalWithNoResourceType()
    {
        $dec = new \stdClass();
        $resource = <?php echo $type->getClassName(); ?>::jsonUnserialize(decoded: $dec);
        $this->assertInstanceOf(<?php echo $type->getclassname(); ?>::class, $resource);
    }

    public function testJsonUnmarshalThrowsExceptionWithWrongResourceType()
    {
        $this->expectException(\DomainException::class);

        $dec = new \stdClass();
        $dec-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?> = 'NotAResource';
        <?php echo $type->getClassName(); ?>::jsonUnserialize(decoded: $dec);
    }
<?php
    if (!$version->getSourceMetadata()->isDSTU1()) :
        if ($type->hasResourceTypeParent()
            && $type !== $bundleType
            && 'DomainResource' !== $type->getFHIRName()
            && !$type->getKind()->isResourceContainer($version)) : ?>

    public function testCanTranscodeBundleJSON()
    {
        $client = $this->_getClient();
        $rc = $client->read(
            resourceType: <?php echo $versionTypeEnum; ?>::<?php echo $type->getConstName(false); ?>,
            count: 5,
            format: <?php echo $clientFormatEnum; ?>::JSON,
        );
        if (404 === $rc->getCode()) {
            $this->markTestSkipped(sprintf(
                'Configured test endpoint "%s" has no resources of type "<?php echo $type->getFHIRName(); ?>"',
                $this->_getTestEndpoint(),
            ));
        }
        $this->assertIsString($rc->getResp());
        $this->assertJSON($rc->getResp());
        $this->assertEquals(200, $rc->getCode(), sprintf('Configured test endpoint "%s" returned non-200 response code', $this->_getTestEndpoint()));
        $bundle = <?php echo $bundleType->getClassName(); ?>::jsonUnserialize(
            decoded: $rc->getResp(),
            config: $this->_version->getConfig()->getUnserializeConfig(),
        );
        $entry = $bundle->getEntry();
        $this->assertNotCount(0, $entry);
        foreach($entry as $ent) {
            $resource = $ent->getResource();
            $this->assertInstanceOf(<?php echo $type->getclassname(); ?>::class, $resource);
            $enc = json_encode($resource);
            $this->assertJson($enc);
        }
        $enc = json_encode($bundle);
        $this->assertJson($enc);
        $this->assertJsonStringEqualsJsonString($rc->getResp(), $enc);
    }

    public function testCanTranscodeBundleJSONWithNullConfig()
    {
        $client = $this->_getClient();
        $rc = $client->read(
            resourceType: <?php echo $versionTypeEnum; ?>::<?php echo $type->getConstName(false); ?>,
            count: 5,
            format: <?php echo $clientFormatEnum; ?>::JSON,
        );
        if (404 === $rc->getCode()) {
            $this->markTestSkipped(sprintf(
                'Configured test endpoint "%s" has no resources of type "<?php echo $type->getFHIRName(); ?>"',
                $this->_getTestEndpoint(),
            ));
        }
        $this->assertIsString($rc->getResp());
        $this->assertJSON($rc->getResp());
        $this->assertEquals(200, $rc->getCode(), sprintf('Configured test endpoint "%s" returned non-200 response code', $this->_getTestEndpoint()));
        $bundle = <?php echo $bundleType->getClassName(); ?>::jsonUnserialize(
            decoded: $rc->getResp(),
        );
        $entry = $bundle->getEntry();
        $this->assertNotCount(0, $entry);
        foreach($entry as $ent) {
            $resource = $ent->getResource();
            $this->assertInstanceOf(<?php echo $type->getclassname(); ?>::class, $resource);
            $enc = json_encode($resource);
            $this->assertJson($enc);
        }
        $enc = json_encode($bundle);
        $this->assertJson($enc);
        $this->assertJsonStringEqualsJsonString($rc->getResp(), $enc);
    }

    public function testCanTranscodeBundleXML()
    {
        $client = $this->_getClient();
        $rc = $client->read(
            resourceType: <?php echo $versionTypeEnum; ?>::<?php echo $type->getConstName(false); ?>,
            count: 5,
            format: <?php echo $clientFormatEnum; ?>::XML,
        );
        if (404 === $rc->getCode()) {
            $this->markTestSkipped(sprintf(
                'Configured test endpoint "%s" has no resources of type "<?php echo $type->getFHIRName(); ?>"',
                $this->_getTestEndpoint(),
            ));
        }
        $this->assertIsString($rc->getResp());
        $this->assertEquals(200, $rc->getCode(), sprintf('Configured test endpoint "%s" returned non-200 response code', $this->_getTestEndpoint()));
        $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize(
            element: $rc->getResp(),
            config: $this->_version->getConfig()->getUnserializeConfig(),
        );
        $entry = $bundle->getEntry();
        $this->assertNotCount(0, $entry);
        foreach($entry as $ent) {
            $resource = $ent->getResource();
            $this->assertInstanceOf(<?php echo $type->getclassname(); ?>::class, $resource);
        }
        $xw = $bundle->xmlSerialize(config: $this->_version->getConfig()->getSerializeConfig());
        $this->assertXmlStringEqualsXmlString($rc->getResp(), $xw->outputMemory());
    }
 
    public function testCanTranscodeBundleXMLWithNullConfig()
    {
        $client = $this->_getClient();
        $rc = $client->read(
            resourceType: <?php echo $versionTypeEnum; ?>::<?php echo $type->getConstName(false); ?>,
            count: 5,
            format: <?php echo $clientFormatEnum; ?>::XML,
        );
        if (404 === $rc->getCode()) {
            $this->markTestSkipped(sprintf(
                'Configured test endpoint "%s" has no resources of type "<?php echo $type->getFHIRName(); ?>"',
                $this->_getTestEndpoint(),
            ));
        }
        $this->assertIsString($rc->getResp());
        $this->assertEquals(200, $rc->getCode(), sprintf('Configured test endpoint "%s" returned non-200 response code', $this->_getTestEndpoint()));
        $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize(
            element: $rc->getResp(),
        );
        $entry = $bundle->getEntry();
        $this->assertNotCount(0, $entry);
        foreach($entry as $ent) {
            $resource = $ent->getResource();
            $this->assertInstanceOf(<?php echo $type->getclassname(); ?>::class, $resource);
        }
        $xw = $bundle->xmlSerialize(config: $this->_version->getConfig()->getSerializeConfig());
        $this->assertXmlStringEqualsXmlString($rc->getResp(), $xw->outputMemory());
    }
<?php   endif;
    endif; // end dstu2+ integration tests
endif; ?>}
<?php return ob_get_clean();
