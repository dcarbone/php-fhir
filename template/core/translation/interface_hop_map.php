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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$versionInterface = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$codeMap = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODE_MAP);
$fieldRule = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_ENUM_FIELD_RULE);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($versionInterface);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?>

{
    public function getSourceVersion(): VersionInterface;

    public function getTargetVersion(): VersionInterface;

    /** @return array<string, array<string, <?php echo $fieldRule->getFullyQualifiedName(true); ?>>> type name => field name => rule */
    public function getFieldRules(): array;

    /** @return array<string, string> source type name => target type name */
    public function getTypeRenames(): array;

    /** @return string[] */
    public function getRemovedTypes(): array;

    /** @return <?php echo $codeMap->getFullyQualifiedName(true); ?>[] */
    public function getCodeMaps(): array;
}
<?php return ob_get_clean();
