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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$versionCoreFiles = $version->getVersionCoreFiles();

$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);

$parentType = $type->getParentType();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$interfaces = $type->getDirectlyImplementedInterfaces();
$traits = $type->getDirectlyUsedTraits();

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $type->getFullyQualifiedNamespace(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php
echo ImportUtils::compileImportStatements($type->getImports());

echo "\n";

if ('' !== $classDocumentation) : ?>/**
<?php echo $classDocumentation; ?>
 */
<?php endif;
if ($type->isUsedAsProperty()) : ?>
#[\Attribute(\Attribute::TARGET_PROPERTY)]
<?php endif;
// -- class definition
if ($type->isAbstract()) : ?>abstract <?php endif; ?>class <?php echo $type->getClassName(); ?><?php if (null !== $parentType) : ?> extends <?php echo $parentType->getClassName(); endif; ?>
<?php if ([] !== $interfaces) : ?> implements <?php echo implode(', ', array_keys($interfaces)); endif; ?>

{<?php if ([] !== $traits) : ?>

    use <?php echo implode(",\n        ", array_keys($traits)); ?>;

<?php endif; ?>
    // name of FHIR type this class describes
    public const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;

<?php
return ob_get_clean();
