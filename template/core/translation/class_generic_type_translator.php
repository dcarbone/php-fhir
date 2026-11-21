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
$leafTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_LEAF_TYPE);
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
$contextClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONTEXT);
$fieldMetadataClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA);
$fieldMetadataFactoryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA_FACTORY);
$fieldMetadataFactoryInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_FIELD_METADATA_FACTORY);
$codingCodeMapperClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODING_CODE_MAPPER);
$xverExtensionEmitterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_XVER_EXTENSION_EMITTER);
$xverExtensionUnfolderClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_XVER_EXTENSION_UNFOLDER);
$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $leafTypeInterface,
    $resourceTypeInterface,
    $contextClass,
    $fieldMetadataFactoryClass,
    $fieldMetadataFactoryInterface,
    $codingCodeMapperClass,
    $xverExtensionEmitterClass,
    $xverExtensionUnfolderClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?> implements TypeTranslatorInterface
{
    private readonly <?php echo $codingCodeMapperClass; ?> $codingCodeMapper;
    private readonly <?php echo $xverExtensionEmitterClass; ?> $xverEmitter;
    private readonly <?php echo $xverExtensionUnfolderClass; ?> $xverUnfolder;

    public function __construct(
        private readonly FieldMetadataFactoryInterface $metadataFactory = new FieldMetadataFactory(),
    ) {
        $this->codingCodeMapper = new <?php echo $codingCodeMapperClass; ?>();
        $this->xverEmitter = new <?php echo $xverExtensionEmitterClass; ?>($metadataFactory);
        $this->xverUnfolder = new <?php echo $xverExtensionUnfolderClass; ?>($metadataFactory);
    }

    public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
    {
        $typeName = $source->_getFHIRTypeName();
        $hopMap = $context->getHopMap();
        if (null !== $hopMap && in_array($typeName, $hopMap->getRemovedTypes(), true)) {
            return null;
        }
        $targetClass = $this->resolveTargetClass($typeName, $context, $context->getExpectedTargetClass());
        if (null === $targetClass) {
            return null;
        }

        $target = new $targetClass();
        if ($source instanceof LeafTypeInterface || $target instanceof LeafTypeInterface) {
            if (!$source instanceof LeafTypeInterface || !$target instanceof LeafTypeInterface) {
                return null;
            }
            if (!method_exists($source, 'getValue') || !method_exists($target, 'setValue')) {
                throw new \UnexpectedValueException(sprintf(
                    'FHIR leaf type "%s" must expose getValue() and setValue().',
                    $source::class,
                ));
            }
            $value = $source->getValue();
            if ('dateTime-primitive' === $source->_getFHIRTypeName()
                && 'date-primitive' === $target->_getFHIRTypeName()
                && is_string($value)
                && str_contains($value, 'T')) {
                return null;
            }
            $target->setValue($value);
            // Numeric primitives hand their value over as a string; keep the source's JSON representation.
            if (method_exists($source, '_getJSONAsString') && method_exists($target, '_setJSONAsString')) {
                $target->_setJSONAsString($source->_getJSONAsString());
            }
            return $target;
        }

        $sourceFields = $this->metadataFactory->forType($source);
        $targetFields = $this->metadataFactory->forType($target);
        $codeMapping = 'Coding' === $typeName && null !== $hopMap
            ? $this->codingCodeMapper->map($source, $sourceFields, $hopMap, $context)
            : null;
        $sourceHasValues = false;
        $targetHasValues = false;
        $unmappedValues = [];
        foreach ($sourceFields as $fieldName => $sourceField) {
            $getter = $sourceField->getGetter();
            if (!method_exists($source, $getter)) {
                throw new \UnexpectedValueException(sprintf(
                    'Generated type "%s" is missing field getter "%s()".',
                    $source::class,
                    $getter,
                ));
            }

            $value = $source->{$getter}();
            if (null === $value || [] === $value) {
                continue;
            }
            $sourceHasValues = true;
            if (!isset($targetFields[$fieldName])) {
                // Handled after the loop so extensions emitted for it are appended to, not replaced by, the
                // target's translated extension fields.
                $unmappedValues[$fieldName] = $value;
                continue;
            }

            $targetField = $targetFields[$fieldName];
            if ('Coding' === $typeName && 'version' === $fieldName && null !== $codeMapping
                && ($codeMapping->isFailure()
                    || ($codeMapping->changesSystem() && null === $codeMapping->getTargetVersion()))) {
                $this->handleUnmappedValue($context, $fieldName, $value);
                continue;
            }
            $values = $sourceField->isCollection() ? $value : [$value];
            if (!is_array($values)) {
                throw new \UnexpectedValueException(sprintf(
                    'Generated collection getter "%s::%s()" must return an array.',
                    $source::class,
                    $getter,
                ));
            }
            if (!$targetField->isCollection() && count($values) > 1) {
                foreach ($values as $item) {
                    $this->handleUnmappedValue($context, $fieldName, $item);
                }
                continue;
            }

            $translatedValues = [];
            foreach ($values as $item) {
                if ($item instanceof TypeInterface) {
                    $translated = $context->translateField($fieldName, $item, $targetField->getType());
                    if (null === $translated) {
                        continue;
                    }
                    if (!is_a($translated, $targetField->getType())) {
                        $context->handleUnmappableField($fieldName, $item->_getFHIRTypeName());
                        continue;
                    }
                    if ('Coding' === $typeName && null !== $codeMapping
                        && in_array($fieldName, ['system', 'code'], true)) {
                        if ($codeMapping->isFailure()) {
                            continue;
                        }
                        if (!method_exists($translated, 'setValue')) {
                            throw new \UnexpectedValueException(sprintf(
                                'Mapped Coding field "%s" must expose setValue().',
                                $fieldName,
                            ));
                        }
                        $translated->setValue($codeMapping->getTargetValue($fieldName));
                    }
                    $translatedValues[] = $translated;
                } else {
                    $translatedValues[] = $item;
                }
            }

            if ([] === $translatedValues) {
                continue;
            }

            $setter = $targetField->getSetter();
            if (!method_exists($target, $setter)) {
                throw new \UnexpectedValueException(sprintf(
                    'Generated target type "%s" is missing field setter "%s()".',
                    $target::class,
                    $setter,
                ));
            }
            if ($targetField->isCollection()) {
                $target->{$setter}(...$translatedValues);
            } else {
                $target->{$setter}($translatedValues[0]);
            }
            $targetHasValues = true;
        }

        // A map can supply a target version even when the source Coding has no version element.
        if (null !== ($mappedVersion = $codeMapping?->getTargetVersion())
            && $this->setMappedVersion($target, $targetFields, $mappedVersion, $context)) {
            $targetHasValues = true;
        }

        foreach ($unmappedValues as $fieldName => $value) {
            if ($this->xverEmitter->emit($source, $target, $targetFields, $fieldName, $value, $context)) {
                $targetHasValues = true;
                continue;
            }
            $this->handleUnmappedValue($context, $fieldName, $value);
        }

        if ($this->xverUnfolder->unfold($target, $targetFields, $context)) {
            $targetHasValues = true;
        }

        // Resources always yield an instance; only non-resource elements collapse to null when nothing was copied.
        if ($sourceHasValues && !$targetHasValues && !$target instanceof ResourceTypeInterface) {
            return null;
        }

        return $target;
    }

    /** @return null|class-string<TypeInterface> */
    private function resolveTargetClass(
        string $typeName,
        TranslationContext $context,
        null|string $expectedClass,
    ): null|string {
        $typeMap = $context->getTargetVersion()->getTypeMap();
        $targetClass = $typeMap::getTypeClassname($typeName);
        if (null !== $expectedClass
            && (null === $targetClass || !is_a($targetClass, $expectedClass, true))
            && (new \ReflectionClass($expectedClass))->isInstantiable()) {
            $targetClass = $expectedClass;
        }
        if (null !== $targetClass && !is_a($targetClass, TypeInterface::class, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Target type map returned "%s" for FHIR type "%s", but it does not implement %s.',
                $targetClass,
                $typeName,
                TypeInterface::class,
            ));
        }
        return $targetClass;
    }

    /** @param array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>> $targetFields */
    private function setMappedVersion(
        TypeInterface $target,
        array $targetFields,
        string $version,
        TranslationContext $context,
    ): bool {
        $field = $targetFields['version'] ?? null;
        if (null === $field) {
            $context->handleUnmappableField('version', 'string');
            return false;
        }
        $getter = $field->getGetter();
        $setter = $field->getSetter();
        if (!method_exists($target, $getter) || !method_exists($target, $setter)) {
            throw new \UnexpectedValueException('Mapped Coding.version must expose its configured getter and setter.');
        }
        $value = $target->{$getter}();
        if (null === $value) {
            $class = $this->resolveTargetClass('string', $context, $field->getType());
            if (null === $class || !is_a($class, $field->getType(), true)) {
                $context->handleUnmappableField('version', 'string');
                return false;
            }
            $value = new $class();
        }
        if (!$value instanceof TypeInterface || !method_exists($value, 'setValue')) {
            throw new \UnexpectedValueException('Mapped Coding.version must be a value-bearing FHIR type.');
        }
        $value->setValue($version);
        $target->{$setter}($value);
        return true;
    }

    private function handleUnmappedValue(TranslationContext $context, string $fieldName, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->handleUnmappedValue($context, $fieldName, $item);
            }
            return;
        }

        $context->handleUnmappableField(
            $fieldName,
            $value instanceof TypeInterface ? $value->_getFHIRTypeName() : get_debug_type($value),
        );
    }

}
<?php return ob_get_clean();
