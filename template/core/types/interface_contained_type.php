<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports($resourceTypeInterface);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Interface <?php echo $coreFile; ?>

 *
 * This is a meta interface that must never be directly implemented by a class.  It exists purely to ensure type safety
 * throughout the base package.
 */
interface <?php echo $coreFile; ?> extends <?php echo $resourceTypeInterface; ?>

{
    /**
     * The return from this method is used only when json serializing this type
     *
     * @return string
     */
    public function _getResourceType(): string;
}
<?php return ob_get_clean();