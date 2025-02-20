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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php foreach($config->getVersionsIterator() as $version) : ?>
use <?php echo $version->getFullyQualifiedName(false, PHPFHIR_VERSION_CLASSNAME_VERSION); ?> as <?php echo $version->getEnumImportName(); ?>;
<?php endforeach; ?>

enum <?php echo PHPFHIR_ENUM_VERSION; ?> : string
{
<?php foreach ($config->getVersionsIterator() as $version) : ?>
    case <?php echo $version->getConstName(); ?> = <?php echo $version->getEnumImportName(); ?>::NAME;
<?php endforeach; ?>
}
<?php return ob_get_clean();
