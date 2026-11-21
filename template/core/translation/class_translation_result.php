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

$coreFiles = $config->getCoreFiles();
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
$noticeClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_NOTICE);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($resourceTypeInterface, $noticeClass);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?>

{
    /**
     * @param <?php echo $noticeClass->getFullyQualifiedName(true); ?>[] $notices
     */
    public function __construct(
        private readonly ResourceTypeInterface $resource,
        private readonly array $notices = [],
    ) {
        foreach ($this->notices as $notice) {
            if (!$notice instanceof TranslationNotice) {
                throw new \InvalidArgumentException(sprintf(
                    'Translation notices must be instances of %s; %s received.',
                    TranslationNotice::class,
                    get_debug_type($notice),
                ));
            }
        }
    }

    public function getResource(): ResourceTypeInterface
    {
        return $this->resource;
    }

    /**
     * @return <?php echo $noticeClass->getFullyQualifiedName(true); ?>[]
     */
    public function getNotices(): array
    {
        return $this->notices;
    }
}
<?php return ob_get_clean();
