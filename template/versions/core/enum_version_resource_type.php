<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$versionCoreFiles = $version->getCoreFiles();
$imports = $coreFile->getImports();

$versionConstants = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS);

$imports->addCoreFileImport(
    $versionConstants,
);

$types = $version->getDefinition()->getTypes();

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

enum <?php echo $coreFile; ?> : string
{
<?php foreach($types->getNameSortedIterator() as $type) :
    if (!$type->hasResourceTypeParent()
        || 'Bundle' === $type->getFHIRName()
        || 'DomainResource' === $type->getFHIRName()
        || $type->isAbstract()
        || $type->getKind()->isResourceContainer($version)
        ) {
        continue;
    } ?>
    case <?php echo $type->getConstName(false); ?> = <?php echo $type->getTypeNameConst(true) ?>;
<?php endforeach;?>
}

<?php return ob_get_clean();