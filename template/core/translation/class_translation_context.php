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
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$translatorInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_TYPE_TRANSLATOR);
$configClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONFIG);
$noticeClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_NOTICE);
$noticeCollectorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_NOTICE_COLLECTOR);
$hopMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_HOP_MAP);
$unmappableFieldEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_ENUM_ON_UNMAPPABLE_FIELD);
$unmappableFieldException = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_EXCEPTION_UNMAPPABLE_FIELD);
$missingXverDefinitionException = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_EXCEPTION_MISSING_XVER_DEFINITION);
$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $versionInterface,
    $translatorInterface,
    $configClass,
    $noticeClass,
    $noticeCollectorClass,
    $hopMapInterface,
    $unmappableFieldEnum,
    $unmappableFieldException,
    $missingXverDefinitionException,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?>

{
    public function __construct(
        private readonly VersionInterface $sourceVersion,
        private readonly VersionInterface $targetVersion,
        private readonly TranslationConfig $config,
        private readonly string $path,
        private readonly TypeTranslatorInterface $translator,
        private readonly TranslationNoticeCollector $notices,
        private readonly null|HopMapInterface $hopMap = null,
    ) {
    }

    public function getSourceVersion(): VersionInterface
    {
        return $this->sourceVersion;
    }

    public function getTargetVersion(): VersionInterface
    {
        return $this->targetVersion;
    }

    public function getConfig(): TranslationConfig
    {
        return $this->config;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHopMap(): null|HopMapInterface
    {
        return $this->hopMap;
    }

    public function translate(TypeInterface $source): null|TypeInterface
    {
        $translated = $this->translator->translate($source, $this);
        if (null !== $translated) {
            return $translated;
        }

        $this->applyUnmappablePolicy($this->path, $source->_getFHIRTypeName());
        return null;
    }

    public function translateField(string $fieldName, TypeInterface $source): null|TypeInterface
    {
        $path = '' === $this->path ? $fieldName : $this->path . '.' . $fieldName;
        $context = new self(
            $this->sourceVersion,
            $this->targetVersion,
            $this->config,
            $path,
            $this->translator,
            $this->notices,
            $this->hopMap,
        );
        return $context->translate($source);
    }

    public function handleUnmappableField(string $fieldName, string $sourceTypeName): void
    {
        $path = '' === $this->path ? $fieldName : $this->path . '.' . $fieldName;
        $this->applyUnmappablePolicy($path, $sourceTypeName);
    }

    public function addNotice(TranslationNotice $notice): void
    {
        $this->notices->add($notice);
    }

    private function applyUnmappablePolicy(string $path, string $sourceTypeName): void
    {
        $policy = $this->config->getOnUnmappableField();
        if (OnUnmappableFieldEnum::EXTENSION === $policy) {
            throw new MissingXverDefinitionException(
                $path,
                $this->sourceVersion->getName(),
                $this->targetVersion->getName(),
            );
        }

        $message = sprintf(
            'Source value of type "%s" at "%s" has no target representation when translating %s to %s.',
            $sourceTypeName,
            $path,
            $this->sourceVersion->getName(),
            $this->targetVersion->getName(),
        );
        if (OnUnmappableFieldEnum::THROW === $policy) {
            throw new UnmappableFieldException($message);
        }

        $this->addNotice(new TranslationNotice(
            $path,
            'dropped',
            $this->sourceVersion->getName(),
            $this->targetVersion->getName(),
            $message,
        ));
    }
}
<?php return ob_get_clean();
