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
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
$translatorInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_TYPE_TRANSLATOR);
$configClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONFIG);
$versionRegistryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_VERSION_REGISTRY);
$dispatcherClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_TYPE_TRANSLATOR_DISPATCHER);
$hopMapRegistryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_HOP_MAP_REGISTRY);
$metadataFactoryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA_FACTORY);
$metadataFactoryInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_FIELD_METADATA_FACTORY);
$contextClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONTEXT);
$resultClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_RESULT);
$noticeCollectorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_NOTICE_COLLECTOR);
$unmappableFieldException = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_EXCEPTION_UNMAPPABLE_FIELD);
$ambiguousRouteException = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_EXCEPTION_AMBIGUOUS_ROUTE);
$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $versionInterface,
    $resourceTypeInterface,
    $translatorInterface,
    $configClass,
    $versionRegistryClass,
    $dispatcherClass,
    $hopMapRegistryClass,
    $metadataFactoryClass,
    $metadataFactoryInterface,
    $contextClass,
    $resultClass,
    $noticeCollectorClass,
    $unmappableFieldException,
    $ambiguousRouteException,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?>

{
    private readonly TypeTranslatorDispatcher $dispatcher;

    public function __construct(
        private readonly TranslationConfig $config = new TranslationConfig(),
        FieldMetadataFactoryInterface $metadataFactory = new FieldMetadataFactory(),
    ) {
        $this->dispatcher = new TypeTranslatorDispatcher($metadataFactory);
    }

    public function register(
        VersionInterface $from,
        VersionInterface $to,
        string $typeName,
        TypeTranslatorInterface $translator,
    ): static {
        $this->dispatcher->register($from, $to, $typeName, $translator);
        return $this;
    }

    public function translate(ResourceTypeInterface $source, VersionInterface $target): TranslationResult
    {
        $sourceVersion = VersionRegistry::getVersionForType($source);
        $chain = $this->buildChain($sourceVersion, $target, $source->_getFHIRTypeName());
        if ([] === $chain) {
            return new TranslationResult($source);
        }

        $resource = $source;
        $notices = new TranslationNoticeCollector();
        $from = $sourceVersion;
        foreach ($chain as $to) {
            $context = new TranslationContext(
                $from,
                $to,
                $this->config,
                $resource->_getFHIRTypeName(),
                $this->dispatcher,
                $notices,
                HopMapRegistry::getMap($from, $to),
            );
            $translated = $context->translate($resource);
            if (null === $translated) {
                throw new UnmappableFieldException(sprintf(
                    'FHIR resource type "%s" has no representation in %s.',
                    $resource->_getFHIRTypeName(),
                    $to->getName(),
                ));
            }
            if (!$translated instanceof ResourceTypeInterface) {
                throw new \UnexpectedValueException(sprintf(
                    'Translator returned %s for a resource; expected %s.',
                    $translated::class,
                    ResourceTypeInterface::class,
                ));
            }
            $resource = $translated;
            $from = $to;
        }

        return new TranslationResult($resource, $notices->getNotices());
    }

    /**
     * @return VersionInterface[]
     */
    private function buildChain(VersionInterface $from, VersionInterface $to, string $rootTypeName): array
    {
        if ($from->getName() === $to->getName()) {
            return [];
        }
        if ($this->dispatcher->has($from, $to, $rootTypeName)) {
            return [$to];
        }

        $versions = VersionRegistry::getVersions();
        $registeredNames = [];
        foreach ($versions as $version) {
            $registeredNames[$version->getName()] = true;
        }
        if (!isset($registeredNames[$from->getName()]) || !isset($registeredNames[$to->getName()])) {
            throw new \InvalidArgumentException(sprintf(
                'Both source version "%s" and target version "%s" must be included in the generated version registry.',
                $from->getName(),
                $to->getName(),
            ));
        }
        if (null !== HopMapRegistry::getMap($from, $to)) {
            return [$to];
        }

        $fromInteger = $from->getFHIRVersionInteger();
        $toInteger = $to->getFHIRVersionInteger();
        if ($fromInteger === $toInteger) {
            throw new AmbiguousTranslationRouteException(sprintf(
                'Cannot automatically translate between "%s" and "%s": both represent FHIR release %s. Register a direct translator.',
                $from->getName(),
                $to->getName(),
                $from->getFHIRShortVersion(),
            ));
        }

        $direction = $toInteger > $fromInteger ? 1 : -1;
        $intermediate = [];
        foreach ($versions as $version) {
            $versionInteger = $version->getFHIRVersionInteger();
            if (($direction > 0 && $versionInteger > $fromInteger && $versionInteger < $toInteger)
                || ($direction < 0 && $versionInteger < $fromInteger && $versionInteger > $toInteger)) {
                $intermediate[$versionInteger][] = $version;
            }
        }

        foreach ($intermediate as $versionInteger => $sameReleaseVersions) {
            if (count($sameReleaseVersions) > 1) {
                $names = array_map(
                    static fn(VersionInterface $version): string => $version->getName(),
                    $sameReleaseVersions,
                );
                throw new AmbiguousTranslationRouteException(sprintf(
                    'Cannot automatically translate from "%s" to "%s": FHIR release integer %d has multiple generated variants (%s). Register a route/translator for the ambiguous hop.',
                    $from->getName(),
                    $to->getName(),
                    $versionInteger,
                    implode(', ', $names),
                ));
            }
        }

        if ($direction > 0) {
            ksort($intermediate);
        } else {
            krsort($intermediate);
        }

        $chain = [];
        foreach ($intermediate as $sameReleaseVersions) {
            $chain[] = $sameReleaseVersions[0];
        }
        $chain[] = $to;
        return $chain;
    }
}
<?php return ob_get_clean();
