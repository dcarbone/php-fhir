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
$fieldRuleEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_ENUM_FIELD_RULE);
$codeMapClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODE_MAP);
$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $leafTypeInterface,
    $resourceTypeInterface,
    $contextClass,
    $fieldMetadataFactoryClass,
    $fieldMetadataFactoryInterface,
    $fieldRuleEnum,
    $codeMapClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?> implements TypeTranslatorInterface
{
    public function __construct(
        private readonly FieldMetadataFactoryInterface $metadataFactory = new FieldMetadataFactory(),
    ) {
    }

    public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
    {
        $typeName = $source->_getFHIRTypeName();
        $hopMap = $context->getHopMap();
        $targetClass = null;
        if (null === $hopMap || !in_array($typeName, $hopMap->getRemovedTypes(), true)) {
            $targetTypeName = $hopMap?->getTypeRenames()[$typeName] ?? $typeName;
            $typeMap = $context->getTargetVersion()->getTypeMap();
            $targetClass = $typeMap::getTypeClassname($targetTypeName);
        }
        $expectedClass = $context->getExpectedTargetClass();
        if (null !== $expectedClass
            && (null === $targetClass || !is_a($targetClass, $expectedClass, true))
            && (new \ReflectionClass($expectedClass))->isInstantiable()) {
            // The target field declares a different type than the source value (e.g. date -> dateTime), so
            // translate into the declared type and copy whatever compatible fields it shares.
            $targetClass = $expectedClass;
        }
        if (null === $targetClass) {
            return null;
        }
        if (!is_a($targetClass, TypeInterface::class, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Target type map returned "%s" for FHIR type "%s", but it does not implement %s.',
                $targetClass,
                $source->_getFHIRTypeName(),
                TypeInterface::class,
            ));
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
            $target->setValue($source->getValue());
            return $target;
        }

        $sourceFields = $this->metadataFactory->forType($source);
        $targetFields = $this->metadataFactory->forType($target);
        $fieldRules = $hopMap?->getFieldRules()[$typeName] ?? [];
        $codeMappingApplied = false;
        $codeMappingFailed = false;
        $mappedSystem = null;
        $mappedCode = null;
        if ('Coding' === $typeName && null !== $hopMap) {
            $sourceSystem = $this->getStringFieldValue($source, $sourceFields, 'system');
            $sourceCode = $this->getStringFieldValue($source, $sourceFields, 'code');
            if (null !== $sourceSystem && null !== $sourceCode) {
                $sourceSystemMatched = false;
                $candidateTargets = [];
                foreach ($hopMap->getCodeMaps() as $codeMap) {
                    if (!$codeMap instanceof CodeMap) {
                        throw new \UnexpectedValueException(sprintf(
                            'Hop map returned %s for code maps; expected %s.',
                            get_debug_type($codeMap),
                            CodeMap::class,
                        ));
                    }
                    if ($codeMap->getSourceSystem() !== $sourceSystem) {
                        continue;
                    }
                    $sourceSystemMatched = true;
                    // A source system may have several maps (e.g. fhir-types -> data-types and -> resource-types);
                    // only maps that actually translate this code are candidates.
                    if ($codeMap->hasSourceCode($sourceCode)
                        && null !== ($candidateCode = $codeMap->getTargetCode($sourceCode))) {
                        $candidateTargets[$codeMap->getTargetSystem() . '|' . $candidateCode] = [
                            $codeMap->getTargetSystem(),
                            $candidateCode,
                        ];
                    }
                }
                if ($sourceSystemMatched) {
                    $codeMappingApplied = true;
                    if (1 === count($candidateTargets)) {
                        [$mappedSystem, $mappedCode] = reset($candidateTargets);
                    } else {
                        $this->handleUnmappedValue($context, 'code', $sourceCode);
                        $codeMappingFailed = true;
                    }
                }
            }
        }
        $sourceHasValues = false;
        $targetHasValues = false;
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
                $rule = $fieldRules[$fieldName] ?? FieldRuleEnum::UNMAPPABLE;
                if (FieldRuleEnum::UNMAPPABLE === $rule) {
                    $this->handleUnmappedValue($context, $fieldName, $value);
                    continue;
                }
                $this->handleUnmappedValue($context, $fieldName, $value);
                continue;
            }

            $targetField = $targetFields[$fieldName];
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
                    if ('Coding' === $typeName && $codeMappingApplied
                        && in_array($fieldName, ['system', 'code'], true)) {
                        if ($codeMappingFailed) {
                            continue;
                        }
                        if (!method_exists($translated, 'setValue')) {
                            throw new \UnexpectedValueException(sprintf(
                                'Mapped Coding field "%s" must expose setValue().',
                                $fieldName,
                            ));
                        }
                        $translated->setValue('system' === $fieldName ? $mappedSystem : $mappedCode);
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

        // Resources always yield an instance; only non-resource elements collapse to null when nothing was copied.
        if ($sourceHasValues && !$targetHasValues && !$target instanceof ResourceTypeInterface) {
            return null;
        }

        return $target;
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

    /**
     * @param array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>> $fields
     */
    private function getStringFieldValue(TypeInterface $owner, array $fields, string $fieldName): null|string
    {
        if (!isset($fields[$fieldName])) {
            return null;
        }
        $getter = $fields[$fieldName]->getGetter();
        if (!method_exists($owner, $getter)) {
            throw new \UnexpectedValueException(sprintf(
                'Generated type "%s" is missing field getter "%s()".',
                $owner::class,
                $getter,
            ));
        }
        $value = $owner->{$getter}();
        if (null === $value) {
            return null;
        }
        if (!$value instanceof TypeInterface || !method_exists($value, 'getValue')) {
            throw new \UnexpectedValueException(sprintf(
                'FHIR field "%s::%s()" must return a value-bearing type.',
                $owner::class,
                $getter,
            ));
        }
        // Elements wrap primitives (e.g. uri -> uri-primitive), so unwrap until a scalar is reached.
        do {
            $value = $value->getValue();
        } while ($value instanceof TypeInterface && method_exists($value, 'getValue'));
        if (null !== $value && !is_string($value)) {
            throw new \UnexpectedValueException(sprintf(
                'FHIR field "%s::%s()" must contain a string value.',
                $owner::class,
                $getter,
            ));
        }
        return $value;
    }
}
<?php return ob_get_clean();
