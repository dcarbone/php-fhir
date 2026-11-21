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
$contextClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONTEXT);
$genericTranslatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_GENERIC_TYPE_TRANSLATOR);
$metadataFactoryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA_FACTORY);
$metadataFactoryInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_FIELD_METADATA_FACTORY);
$versionRegistryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_VERSION_REGISTRY);
$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $versionInterface,
    $translatorInterface,
    $contextClass,
    $genericTranslatorClass,
    $metadataFactoryClass,
    $metadataFactoryInterface,
    $versionRegistryClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?> implements TypeTranslatorInterface
{
    /** @var array<string, <?php echo $translatorInterface->getFullyQualifiedName(true); ?>> */
    private array $translators = [];

    private readonly TypeTranslatorInterface $defaultTranslator;

    public function __construct(
        FieldMetadataFactoryInterface $metadataFactory = new FieldMetadataFactory(),
    ) {
        $this->defaultTranslator = new GenericTypeTranslator($metadataFactory);
    }

    public function register(
        VersionInterface $from,
        VersionInterface $to,
        string $typeName,
        TypeTranslatorInterface $translator,
    ): void {
        if ('' === trim($typeName)) {
            throw new \InvalidArgumentException('FHIR type name must not be empty.');
        }
        $this->translators[$this->translatorKey($from, $to, $typeName)] = $translator;
    }

    public function has(VersionInterface $from, VersionInterface $to, string $typeName): bool
    {
        return isset($this->translators[$this->translatorKey($from, $to, $typeName)]);
    }

    public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
    {
        $from = $context->getSourceVersion();
        $to = $context->getTargetVersion();
        $translator = $this->translators[$this->translatorKey($from, $to, $source->_getFHIRTypeName())]
            ?? $this->defaultTranslator;

        $translated = $translator->translate($source, $context);
        if (null !== $translated) {
            $translatedVersion = VersionRegistry::getVersionForType($translated);
            if ($translatedVersion->getName() !== $to->getName()) {
                throw new \UnexpectedValueException(sprintf(
                    'Translator for "%s" from %s to %s returned a type from %s.',
                    $source->_getFHIRTypeName(),
                    $from->getName(),
                    $to->getName(),
                    $translatedVersion->getName(),
                ));
            }
        }
        return $translated;
    }

    private function translatorKey(VersionInterface $from, VersionInterface $to, string $typeName): string
    {
        return $from->getName() . "\0" . $to->getName() . "\0" . $typeName;
    }
}
<?php return ob_get_clean();
