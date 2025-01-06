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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$imports = $coreFile->getImports();

$imports->addCoreFileImportsByName(PHPFHIR_INTERFACE_CONTAINED_TYPE);

$config = $version->getConfig();
$types = $version->getDefinition()->getTypes();

$containerType = $types->getContainerType($version);
if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKindEnum::RESOURCE_CONTAINER->value,
        TypeKindEnum::RESOURCE_INLINE->value
    ));
}

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Interface <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>

 *
 * This interface is applied to any class that is containable within a <?php echo $version->getName(); ?> <?php echo $containerType->getClassName(); ?> instance
 */
interface <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?> extends <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>

{

}
<?php return ob_get_clean();