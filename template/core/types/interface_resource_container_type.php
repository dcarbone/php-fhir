<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getimports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$containedTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_CONTAINED_TYPE);

$imports->addCoreFileImports(
    $typeInterface,
    $containedTypeInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?> extends <?php echo $typeInterface; ?>

{
    /**
     * Must return the contained resource, or null if one is not set.
     *
     * @return null|<?php echo $containedTypeInterface->getFullyQualifiedName(true); ?>

     */
    public function getContainedType(): null|<?php echo $containedTypeInterface; ?>;

    /**
     * Set or unset the contained type.
     *
     * @param null|<?php echo $containedTypeInterface->getFullyQualifiedName(true); ?> $contained
     * @return static
     */
    public function setContainedType(null|<?php echo $containedTypeInterface; ?> $contained): self;
}
<?php return ob_get_clean();
