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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$imports = $coreFile->getImports();

foreach($config->getVersionsIterator() as $version) {
    $imports->addCoreFileImport(
        coreFile: $version->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION),
        alias: $version->getEnumImportName(),
    );
}

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

enum <?php echo $coreFile; ?> : string
{
<?php foreach ($config->getVersionsIterator() as $version) : ?>
    case <?php echo $version->getConstName(); ?> = <?php echo $version->getEnumImportName(); ?>::NAME;
<?php endforeach; ?>

    public function getSemanticVersion(): string
    {
        return match ($this) {
<?php foreach ($config->getVersionsIterator() as $version) : ?>
            self::<?php echo $version->getConstName(); ?> => <?php echo $version->getEnumImportName(); ?>::FHIR_SEMANTIC_VERSION,
<?php endforeach; ?>
        };
    }

    public function getShortVersion(): string
    {
        return match ($this) {
<?php foreach ($config->getVersionsIterator() as $version) : ?>
            self::<?php echo $version->getConstName(); ?> => <?php echo $version->getEnumImportName(); ?>::FHIR_SHORT_VERSION,
<?php endforeach; ?>
        };
    }

    public function getVersionInteger(): int
    {
        return match ($this) {
<?php foreach ($config->getVersionsIterator() as $version) : ?>
            self::<?php echo $version->getConstName(); ?> => <?php echo $version->getEnumImportName(); ?>::FHIR_INTEGER_VERSION,
<?php endforeach; ?>
        };
    }
}
<?php return ob_get_clean();
