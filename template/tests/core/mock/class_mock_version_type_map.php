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

$coreFiles = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$versionTypeMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_TYPE_MAP);

$mockResourceType = $testCoreFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);

$imports->addCoreFileImports(
    $typeInterface,
    $versionTypeMapInterface,

    $mockResourceType,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $versionTypeMapInterface; ?>

{
    public static function getMap(): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public static function getContainableTypes(): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public static function getTypeClassname(string|\stdClass|\SimpleXMLElement $input): null|string
    {
        return match (true) {
            is_string($input) => <?php echo $mockResourceType; ?>::class,
            $input instanceof \stdClass && isset($input->resourceType) => <?php echo $mockResourceType; ?>::class,
            default => throw new \BadMethodCallException('Not implemented'),
        };
    }

    public static function getContainedTypeClassname(string|\stdClass|\SimpleXMLElement $input): null|string
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public static function isContainableType(string|\stdClass|\SimpleXMLElement|<?php echo $typeInterface; ?> $input): bool
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public static function mustGetContainedTypeClassnameFromXML(\SimpleXMLElement $node): string
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public static function mustGetContainedTypeClassnameFromJSON(\stdClass $decoded): string
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
<?php return ob_get_clean();
