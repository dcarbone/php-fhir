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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

$namespace = $version->getFullyQualifiedName(false);

$types = $version->getDefinition()->getTypes();

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

abstract class <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS; ?>

{
<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public const <?php echo $type->getTypeNameConst(false); ?> = '<?php echo $type->getFHIRName(); ?>';
<?php endforeach;?>

<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public const <?php echo $type->getClassNameConst(false); ?> = '<?php echo str_replace('\\', '\\\\', $type->getFullyQualifiedClassName(true)); ?>';
<?php endforeach;
echo "}\n";
return ob_get_clean();