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
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$types = $version->getDefinition()->getTypes();

$bundleType = $types->getBundleType();

$coreFiles = $version->getConfig()->getCoreFiles();

$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);
$clientClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CLIENT);
$clientFormatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT);
$unexpectedCodeException = $coreFiles->getCoreFileByEntityName(PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE);

$versionCoreFiles = $version->getCoreFiles();

$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
$versionClientClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_CLIENT);
$versionTypeEnum = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_ENUM_VERSION_TYPES);

$imports = new Imports($version->getConfig(), $type->getFullyQualifiedTestNamespace(false), $type->getTestClassName());
$imports->addVersionTypeImports($type);

if ($type->isResourceType()) {
    $imports
        ->addVersionTypeImports(
            $bundleType
        )
        ->addCoreFileImportsByName(
            PHPFHIR_CLIENT_CLASSNAME_CONFIG,
            PHPFHIR_CLIENT_CLASSNAME_CLIENT,
            PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT,
            PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE,
        )
        ->addVersionCoreFileImportsByName(
            $version,
            PHPFHIR_VERSION_CLASSNAME_VERSION,
            PHPFHIR_VERSION_CLASSNAME_VERSION_CLIENT,
            PHPFHIR_VERSION_ENUM_VERSION_TYPES,
        );
}

$typeKind = $type->getKind();

ob_start();

echo '<?php'; ?>

namespace <?php echo $type->getFullyQualifiedTestNamespace(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $type->getTestClassName(); ?> extends TestCase
{<?php if ($type->isResourceType()) : ?>

    protected <?php echo $versionClass->getEntityName(); ?> $_version;

    protected function setUp(): void
    {
        $this->_version = new <?php echo $versionClass->getEntityName(); ?>();
    }

    protected function _getTestEndpoint(): string
    {
        return trim((string)getenv('<?php echo PHPFHIR_TEST_CONSTANT_INTEGRATION_ENDPOINT; ?>'));
    }

    protected function _getClient(): <?php echo $versionClientClass->getEntityName(); ?>

    {
        $testEndpoint = $this->_getTestEndpoint();
        if ('' === $testEndpoint) {
            $this->markTestSkipped('Environment variable <?php echo PHPFHIR_TEST_CONSTANT_INTEGRATION_ENDPOINT; ?> is not defined or empty');
        }
        return new <?php echo $versionClientClass->getEntityName(); ?>(
            new <?php echo $clientClass->getEntityName(); ?>(
                new <?php echo $clientConfigClass->getEntityName(); ?>(
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
        $this->assertInstanceOf('<?php echo $type->getFullyQualifiedClassName(true); ?>', $type);
    }
<?php if ($typeKind === TypeKindEnum::PRIMITIVE) :
    $primitiveType = $type->getPrimitiveType();

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
        $n = new <?php echo $type->getClassName(); ?>('<?php echo $strVal; ?>');
        $this->assertEquals('<?php echo $strVal; ?>', (string)$n);
<?php endforeach; ?>
    }

    public function testCanSetValueFromString()
    {
<?php foreach($strVals as $strVal) : ?>
        $n = new <?php echo $type->getClassName(); ?>;
        $n->setValue('<?php echo $strVal; ?>');
        $this->assertEquals('<?php echo $strVal; ?>', (string)$n);
<?php endforeach; ?>
    }
<?php elseif ($type->isResourceType() && !$type->getKind()->isResourceContainer($version)) :  ?>

    public function testCanTranscodeBundleJSON()
    {
        $client = $this->_getClient();
        $rc = $client->readRaw(
            resourceType: <?php echo $versionTypeEnum->getEntityName(); ?>::<?php echo $type->getConstName(false); ?>,
            format: <?php echo $clientFormatEnum->getEntityName(); ?>::JSON,
            count: 5,
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
            json: $rc->getResp(),
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
<?php endif; ?>
}
<?php return ob_get_clean();
