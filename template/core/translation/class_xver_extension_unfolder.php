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
$primitiveContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE);
$contextClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONTEXT);
$fieldMetadataClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA);
$fieldMetadataFactoryInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_FIELD_METADATA_FACTORY);
$hopMapRegistryClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_HOP_MAP_REGISTRY);
$xverExtensionClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_XVER_EXTENSION);
$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $primitiveContainerInterface,
    $contextClass,
    $fieldMetadataFactoryInterface,
    $hopMapRegistryClass,
    $xverExtensionClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Restores fields of the target version that the source version carried as official HL7 cross-version extensions.
 *
 * The inverse of the emitter: the extensions it would write when translating from the target version back to the
 * source version are the ones recognised here.
 */
final class <?php echo $coreFile; ?>

{
    private const DATATYPE_EXTENSION_URL = 'http://hl7.org/fhir/StructureDefinition/_datatype';

    /** @var array<class-string, array<string, array<string, <?php echo $xverExtensionClass->getFullyQualifiedName(true); ?>>>> */
    private array $indexes = [];

    public function __construct(private readonly FieldMetadataFactoryInterface $metadataFactory)
    {
    }

    /**
     * Moves extensions that carry a field of $target back into that field, removing them from $target.  An extension
     * stays where it is unless all of its content fits the field exactly, including when the field already holds
     * a value.
     *
     * @param array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>> $targetFields
     */
    public function unfold(TypeInterface $target, array $targetFields, TranslationContext $context): bool
    {
        $index = null;
        $unfolded = false;
        foreach (['extension' => false, 'modifierExtension' => true] as $holderName => $modifier) {
            $holderField = $targetFields[$holderName] ?? null;
            if (null === $holderField
                || !method_exists($target, $holderField->getGetter())
                || !method_exists($target, $holderField->getSetter())) {
                continue;
            }
            $extensions = $target->{$holderField->getGetter()}();
            if (!is_array($extensions) || [] === $extensions) {
                continue;
            }
            $index ??= $this->index($context);
            if ([] === $index) {
                return false;
            }

            $remaining = [];
            foreach ($extensions as $extension) {
                if (!$extension instanceof TypeInterface
                    || !$this->unfoldExtension($target, $targetFields, $extension, $modifier, $index, $context)) {
                    $remaining[] = $extension;
                }
            }
            if (count($remaining) !== count($extensions)) {
                $target->{$holderField->getSetter()}(...$remaining);
                $unfolded = true;
            }
        }

        return $unfolded;
    }

    /**
     * @param array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>> $targetFields
     * @param array<string, array<string, <?php echo $xverExtensionClass->getFullyQualifiedName(true); ?>>> $index
     */
    private function unfoldExtension(
        TypeInterface $target,
        array $targetFields,
        TypeInterface $extension,
        bool $modifier,
        array $index,
        TranslationContext $context,
    ): bool {
        $populated = $this->populated($extension);
        $url = $this->urlOf($populated);
        if (null === $url || !isset($index[$url])) {
            return false;
        }

        $typeName = $target->_getFHIRTypeName();
        foreach ($index[$url] as $key => $definition) {
            if ($definition->isModifier() !== $modifier || $key !== $typeName . '.' . $definition->getField()) {
                continue;
            }
            $field = $targetFields[$definition->getField()] ?? null;
            if (null === $field || !method_exists($target, $field->getGetter()) || !method_exists($target, $field->getSetter())) {
                continue;
            }
            $current = $target->{$field->getGetter()}();
            if (!$field->isCollection() && null !== $current) {
                continue;
            }
            $value = $this->unfoldValue($definition, $field->getType(), $populated, $context);
            if (null === $value) {
                continue;
            }
            if ($field->isCollection()) {
                $target->{$field->getSetter()}(...[...$current, $value]);
            } else {
                $target->{$field->getSetter()}($value);
            }
            return true;
        }

        return false;
    }

    /**
     * @param class-string $fieldType
     * @param array<string, mixed> $populated Populated fields of the extension, keyed by field name.
     */
    private function unfoldValue(
        XverExtension $definition,
        string $fieldType,
        array $populated,
        TranslationContext $context,
    ): null|TypeInterface
    {
        unset($populated['url']);
        if ($definition->isComplex()) {
            return $this->unfoldChildren($definition, $fieldType, $populated, $context);
        }

        if (1 !== count($populated)) {
            return null;
        }
        $name = array_key_first($populated);
        $value = $populated[$name];
        if (!str_starts_with((string)$name, 'value') || !$value instanceof TypeInterface) {
            return null;
        }
        if ($value::class === $fieldType) {
            return $value;
        }

        // Enum-bound elements (e.g. Account.status) are carried as plain codes.
        if ('valueCode' !== $name
            || !$value instanceof PrimitiveContainerTypeInterface
            || !is_a($fieldType, PrimitiveContainerTypeInterface::class, true)
            || !method_exists($fieldType, 'setValue')) {
            return null;
        }
        return $this->translateEnumValue($value, $fieldType, $context);
    }

    /**
     * Converts a plain code primitive to an enum-bound container, translating its element metadata.
     *
     * @param class-string<TypeInterface> $targetClass
     */
    private function translateEnumValue(
        PrimitiveContainerTypeInterface $source,
        string $targetClass,
        TranslationContext $context,
    ): null|TypeInterface {
        $value = method_exists($source, 'getValue') ? $source->getValue() : null;
        $valueType = $value instanceof TypeInterface ? $value->_getFHIRTypeName() : null;
        if ($value instanceof TypeInterface && method_exists($value, 'getValue')) {
            $value = $value->getValue();
        }
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }
        if (!is_string($value)) {
            return null;
        }

        if (!(new \ReflectionClass($targetClass))->isInstantiable()) {
            $typeMap = $context->getTargetVersion()->getTypeMap();
            $concreteClass = null === $valueType ? null : $typeMap::getTypeClassname($valueType);
            if (null === $concreteClass || !is_a($concreteClass, $targetClass, true)) {
                return null;
            }
            $targetClass = $concreteClass;
        }

        $target = new $targetClass();
        if (!$target instanceof PrimitiveContainerTypeInterface || !method_exists($target, 'setValue')) {
            return null;
        }
        $target->setValue($value);

        $sourceFields = $this->metadataFactory->forType($source);
        $targetFields = $this->metadataFactory->forType($target);
        foreach ($sourceFields as $fieldName => $sourceField) {
            if ('value' === $fieldName) {
                continue;
            }
            $sourceValue = $source->{$sourceField->getGetter()}();
            if (null === $sourceValue || [] === $sourceValue) {
                continue;
            }
            $targetField = $targetFields[$fieldName] ?? null;
            if (null === $targetField) {
                return null;
            }

            $translatedValues = [];
            foreach (is_array($sourceValue) ? $sourceValue : [$sourceValue] as $sourceItem) {
                if (!$sourceItem instanceof TypeInterface) {
                    return null;
                }
                $translatedItem = $context->translateField($fieldName, $sourceItem, $targetField->getType());
                if (null === $translatedItem || !is_a($translatedItem, $targetField->getType())) {
                    return null;
                }
                $translatedValues[] = $translatedItem;
            }
            if ([] === $translatedValues) {
                continue;
            }

            if ($targetField->isCollection()) {
                $target->{$targetField->getSetter()}(...$translatedValues);
            } else {
                $target->{$targetField->getSetter()}($translatedValues[0]);
            }
        }

        return $target;
    }

    /**
     * @param class-string $fieldType
     * @param array<string, mixed> $populated
     */
    private function unfoldChildren(
        XverExtension $definition,
        string $fieldType,
        array $populated,
        TranslationContext $context,
    ): null|TypeInterface
    {
        $children = $populated['extension'] ?? null;
        if (1 !== count($populated) || !is_array($children) || !is_a($fieldType, TypeInterface::class, true)) {
            return null;
        }

        $result = new $fieldType();
        $resultFields = $this->metadataFactory->forType($result);
        $collected = [];
        foreach ($children as $child) {
            if (!$child instanceof TypeInterface) {
                return null;
            }
            $childPopulated = $this->populated($child);
            $name = $this->urlOf($childPopulated);
            if (null === $name) {
                return null;
            }
            if (self::DATATYPE_EXTENSION_URL === $name) {
                continue;
            }

            $matched = false;
            foreach ($definition->getChildren() as $childDefinition) {
                $field = $resultFields[$childDefinition->getField()] ?? null;
                if ($childDefinition->getUrl() !== $name || null === $field) {
                    continue;
                }
                $value = $this->unfoldValue($childDefinition, $field->getType(), $childPopulated, $context);
                if (null !== $value) {
                    $collected[$childDefinition->getField()][] = $value;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return null;
            }
        }
        if ([] === $collected) {
            return null;
        }

        foreach ($collected as $fieldName => $values) {
            $field = $resultFields[$fieldName];
            if ($field->isCollection()) {
                $result->{$field->getSetter()}(...$values);
            } elseif (1 === count($values)) {
                $result->{$field->getSetter()}($values[0]);
            } else {
                return null;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed> Every field of $element that holds a value, keyed by field name.
     */
    private function populated(TypeInterface $element): array
    {
        $populated = [];
        foreach ($this->metadataFactory->forType($element) as $name => $field) {
            $value = $element->{$field->getGetter()}();
            if (null !== $value && [] !== $value) {
                $populated[$name] = $value;
            }
        }
        return $populated;
    }

    /**
     * @param array<string, mixed> $populated
     */
    private function urlOf(array $populated): null|string
    {
        $url = $populated['url'] ?? null;
        $value = $url instanceof TypeInterface && method_exists($url, 'getValue') ? $url->getValue() : null;
        return is_string($value) && '' !== $value ? $value : null;
    }

    /**
     * The extensions the translation back to the source version would emit, by URL, then by "<type>.<field>".
     *
     * @return array<string, array<string, <?php echo $xverExtensionClass->getFullyQualifiedName(true); ?>>>
     */
    private function index(TranslationContext $context): array
    {
        $reverse = <?php echo $hopMapRegistryClass; ?>::getMap($context->getTargetVersion(), $context->getSourceVersion());
        if (null === $reverse) {
            return [];
        }
        if (!isset($this->indexes[$reverse::class])) {
            $index = [];
            foreach ($reverse->getXverExtensions() as $key => $definition) {
                $index[$definition->getUrl()][$key] = $definition;
            }
            $this->indexes[$reverse::class] = $index;
        }
        return $this->indexes[$reverse::class];
    }
}
<?php return ob_get_clean();
