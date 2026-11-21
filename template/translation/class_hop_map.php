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
$fieldRule = $imports->addCoreFileImport(
    $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_ENUM_FIELD_RULE),
)->getImportedName();
$codeMapCoreFile = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODE_MAP);
$codeMapClass = null;
if ([] !== $definition->getCodeMaps()) {
    $codeMapClass = $imports->addCoreFileImport($codeMapCoreFile)->getImportedName();
}
// Source and target version classes share a short name; Imports assigns the second one an alias.
$sourceVersion = $imports->addCoreFileImport(
    $source->getVersionCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION),
)->getImportedName();
$targetVersion = $imports->addCoreFileImport(
    $target->getVersionCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION),
)->getImportedName();

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $className; ?> implements <?php echo $hopMapInterface; ?>

{
    private const _FIELD_RULES = [
<?php foreach ($definition->getFieldRules() as $typeName => $fields) : ?>
        <?php echo var_export($typeName, true); ?> => [
<?php foreach ($fields as $fieldName => $rule) : ?>
            <?php echo var_export($fieldName, true); ?> => <?php echo $fieldRule; ?>::<?php echo $rule->name; ?>,
<?php endforeach; ?>
        ],
<?php endforeach; ?>
    ];
    private const _TYPE_RENAMES = [
<?php foreach ($definition->getTypeRenames() as $sourceType => $targetType) : ?>
        <?php echo var_export($sourceType, true); ?> => <?php echo var_export($targetType, true); ?>,
<?php endforeach; ?>
    ];
    private const _REMOVED_TYPES = [
<?php foreach ($definition->getRemovedTypes() as $removedType) : ?>
        <?php echo var_export($removedType, true); ?>,
<?php endforeach; ?>
    ];
    /** @var null|<?php echo $codeMapCoreFile->getFullyQualifiedName(true); ?>[] */
    private static null|array $_codeMaps = null;

    public function getSourceVersion(): <?php echo $versionInterface; ?>

    {
        return new <?php echo $sourceVersion; ?>();
    }

    public function getTargetVersion(): <?php echo $versionInterface; ?>

    {
        return new <?php echo $targetVersion; ?>();
    }

    public function getFieldRules(): array
    {
        return self::_FIELD_RULES;
    }

    public function getTypeRenames(): array
    {
        return self::_TYPE_RENAMES;
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
}
<?php return ob_get_clean();
