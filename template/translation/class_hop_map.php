<?php declare(strict_types=1);

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Translation\XverExtensionDefinition;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $source */
/** @var \DCarbone\PHPFHIR\Version $target */
/** @var \DCarbone\PHPFHIR\Translation\HopMapDefinition $definition */
/** @var string $className */

$config = $source->getConfig();
$namespace = $config->getFullyQualifiedName(false, 'Translation', 'Maps');

$imports = new Imports($config, $namespace, $className);
$versionInterface = $imports->addCoreFileImport(
    $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION),
)->getImportedName();
$hopMapInterface = $imports->addCoreFileImport(
    $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_HOP_MAP),
)->getImportedName();
$codeMapCoreFile = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODE_MAP);
$codeMapClass = null;
if ([] !== $definition->getCodeMaps()) {
    $codeMapClass = $imports->addCoreFileImport($codeMapCoreFile)->getImportedName();
}
$xverExtensionCoreFile = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_XVER_EXTENSION);
$xverExtensionClass = null;
if ([] !== $definition->getXverExtensions()) {
    $xverExtensionClass = $imports->addCoreFileImport($xverExtensionCoreFile)->getImportedName();
}
// Source and target version classes share a short name; Imports assigns the second one an alias.
$sourceVersion = $imports->addCoreFileImport(
    $source->getVersionCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION),
)->getImportedName();
$targetVersion = $imports->addCoreFileImport(
    $target->getVersionCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION),
)->getImportedName();

$renderStrings = static fn(array $values): string => '[' . implode(', ', array_map(
    static fn(string $value): string => var_export($value, true),
    $values,
)) . ']';

$renderXverExtension = static function (XverExtensionDefinition $extension, string $indent) use (
    &$renderXverExtension,
    $renderStrings,
    $xverExtensionClass,
): string {
    $children = '[]';
    if ([] !== $extension->children) {
        $childIndent = $indent . '    ';
        $children = "[\n";
        foreach ($extension->children as $child) {
            $children .= $childIndent . $renderXverExtension($child, $childIndent) . ",\n";
        }
        $children .= $indent . ']';
    }
    return sprintf(
        'new %s(%s, %s, %s, %s, %s, %s)',
        $xverExtensionClass,
        var_export($extension->url, true),
        var_export($extension->field, true),
        var_export($extension->modifier, true),
        $renderStrings($extension->valueTypes),
        $children,
        var_export($extension->datatype, true),
    );
};

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $className; ?> implements <?php echo $hopMapInterface; ?>

{
    private const _REMOVED_TYPES = [
<?php foreach ($definition->getRemovedTypes() as $removedType) : ?>
        <?php echo var_export($removedType, true); ?>,
<?php endforeach; ?>
    ];
    /** @var null|<?php echo $codeMapCoreFile->getFullyQualifiedName(true); ?>[] */
    private static null|array $_codeMaps = null;
    /** @var null|array<string, <?php echo $xverExtensionCoreFile->getFullyQualifiedName(true); ?>> */
    private static null|array $_xverExtensions = null;

    public function getSourceVersion(): <?php echo $versionInterface; ?>

    {
        return new <?php echo $sourceVersion; ?>();
    }

    public function getTargetVersion(): <?php echo $versionInterface; ?>

    {
        return new <?php echo $targetVersion; ?>();
    }

    public function getRemovedTypes(): array
    {
        return self::_REMOVED_TYPES;
    }

    public function getCodeMaps(): array
    {
        if (null === self::$_codeMaps) {
            self::$_codeMaps = [
<?php foreach ($definition->getCodeMaps() as $codeMap) : ?>
                new <?php echo $codeMapClass; ?>(
                    <?php echo var_export($codeMap->getSourceSystem(), true); ?>,
                    <?php echo var_export($codeMap->getTargetSystem(), true); ?>,
                    <?php echo var_export($codeMap->getCodeMappings(), true); ?>,
                ),
<?php endforeach; ?>
            ];
        }
        return self::$_codeMaps;
    }

    public function getXverExtensions(): array
    {
        if (null === self::$_xverExtensions) {
            self::$_xverExtensions = [
<?php foreach ($definition->getXverExtensions() as $key => $extension) : ?>
                <?php echo var_export($key, true); ?> => <?php echo $renderXverExtension($extension, '                '); ?>,
<?php endforeach; ?>
            ];
        }
        return self::$_xverExtensions;
    }
}
<?php return ob_get_clean();
