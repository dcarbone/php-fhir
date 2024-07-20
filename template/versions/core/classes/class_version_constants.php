<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Version\VersionCopyright;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version $version */

$namespace = $config->getFullyQualifiedName(false);

$types = $version->getDefinition()->getTypes();

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $version->getCopyright()->getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Class <?php echo PHPFHIR_CLASSNAME_VERSION_CONSTANTS; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
final class <?php echo PHPFHIR_CLASSNAME_VERSION_CONSTANTS; ?> extends <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>

{
    public const VERSION_NAME = '<?php echo $version->getName(); ?>';

    public const VERSION_SOURCE_URL = '<?php echo $version->getSourceUrl(); ?>';
    public const VERSION_SOURCE_VERSION = '<?php echo $version->getCopyright()->getFHIRVersion(false); ?>';
    public const VERSION_SOURCE_GENERATION_DATE = '<?php echo $version->getCopyright()->getFHIRGenerationDate(); ?>';

<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public const <?php echo $type->getTypeNameConst(false); ?> = '<?php echo $type->getFHIRName(); ?>';
<?php endforeach;?>

<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public const <?php echo $type->getClassNameConst(false); ?> = '<?php echo str_replace('\\', '\\\\', $type->getFullyQualifiedClassName(true)); ?>';
<?php endforeach;
echo "}\n";
return ob_get_clean();