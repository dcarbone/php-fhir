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

$unmappableFieldEnum = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_ENUM_ON_UNMAPPABLE_FIELD);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($unmappableFieldEnum);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?>

{
    public function __construct(
        private readonly OnUnmappableFieldEnum $onUnmappableField = OnUnmappableFieldEnum::EXTENSION,
    ) {
    }

    public function getOnUnmappableField(): OnUnmappableFieldEnum
    {
        return $this->onUnmappableField;
    }
}
<?php return ob_get_clean();
