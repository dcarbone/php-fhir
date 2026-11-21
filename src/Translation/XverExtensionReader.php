<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Translation;

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

use DCarbone\PHPFHIR\Config\TranslationPackageConfig;
use DCarbone\PHPFHIR\Enum\ProfileResourceTypeEnum;
use DCarbone\PHPFHIR\Profiles\Profile;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageException;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;

/**
 * Reads the extensions of an HL7 cross-version (xver) package and resolves each against the source version's types.
 *
 * An xver extension is named for the source element it carries, e.g. ".../extension-Account.balance", so the element
 * is found by walking that path through the source version's definition, not by reading the extension's context
 * (which describes the target version).
 */
final class XverExtensionReader
{
    private const URL_PATTERN = '~^http://hl7\.org/fhir/[0-9.]+/StructureDefinition/extension-([A-Za-z]+(?:\.[A-Za-z]+)+)$~';

    /**
     * Extensions that cannot be resolved to a field of the source version (for example those that only carry
     * a removed resource) are omitted, so translation falls back to the unmappable-field policy for them.
     *
     * @return array<string, \DCarbone\PHPFHIR\Translation\XverExtensionDefinition> Keyed by
     *         "<source type FHIR name>.<source field name>".
     * @throws ProfilePackageException
     * @throws ProfileDefinitionException
     */
    public function read(TranslationPackageConfig $config, Types $sourceTypes): array
    {
        $package = ProfilePackageReader::readSource($config->getSource(), ProfileResourceTypeEnum::STRUCTURE_DEFINITION);
        $definitions = [];
        foreach ($package->getProfiles() as $profile) {
            if (!$profile->isExtension() || 1 !== preg_match(self::URL_PATTERN, $profile->getUrl(), $matches)) {
                continue;
            }
            foreach ($this->resolve($profile, explode('.', $matches[1]), $sourceTypes) as $key => $definition) {
                if (isset($definitions[$key])) {
                    throw new ProfileDefinitionException(sprintf(
                        'Extensions "%s" and "%s" both carry source field "%s".',
                        $definitions[$key]->url,
                        $definition->url,
                        $key,
                    ));
                }
                $definitions[$key] = $definition;
            }
        }
        ksort($definitions, SORT_STRING);
        return $definitions;
    }

    /**
     * @param string[] $path Source element path segments, e.g. ["Account", "balance"].
     * @return array<string, \DCarbone\PHPFHIR\Translation\XverExtensionDefinition>
     */
    private function resolve(Profile $profile, array $path, Types $types): array
    {
        $holder = $types->getTypeByName(array_shift($path));
        $fieldName = array_pop($path);
        foreach ($path as $segment) {
            if (null === $holder) {
                return [];
            }
            $properties = $this->properties($holder, $segment);
            $holder = 1 === count($properties) ? $this->propertyType($types, $properties[0]) : null;
        }
        if (null === $holder) {
            return [];
        }

        $modifier = $profile->getElement('Extension')?->isModifier() ?? false;
        $complex = [] !== $profile->getSlices('Extension.extension');
        if (!$complex && !$this->carriesValue($profile)) {
            return [];
        }

        $valueTypes = $complex ? [] : $this->valueTypes($profile, 'Extension');
        $definitions = [];
        foreach ($this->properties($holder, $fieldName, $valueTypes) as $property) {
            $children = [];
            $datatype = null;
            if ($complex) {
                $children = $this->children($profile, 'Extension.extension', $property, $types);
                if (null === $children) {
                    continue;
                }
                $datatype = $this->datatype($profile, 'Extension.extension');
            }
            $definitions[$holder->getFHIRName() . '.' . $property->getName()] = new XverExtensionDefinition(
                url: $profile->getUrl(),
                field: $property->getName(),
                modifier: $modifier,
                valueTypes: $valueTypes,
                children: $children,
                datatype: $datatype,
            );
        }
        return $definitions;
    }

    /**
     * @return null|list<\DCarbone\PHPFHIR\Translation\XverExtensionDefinition> Null if any slice has no source field.
     */
    private function children(Profile $profile, string $sliceParentId, Property $parent, Types $types): null|array
    {
        $valueType = $this->propertyType($types, $parent);
        if (null === $valueType) {
            return null;
        }
        $children = [];
        foreach ($profile->getSlices($sliceParentId) as $name => $slice) {
            if ($slice->isProhibited() || '_datatype' === $name) {
                continue;
            }
            $nestedParentId = $sliceParentId . ':' . $name . '.extension';
            $complex = [] !== $profile->getSlices($nestedParentId);
            $valueTypes = $complex ? [] : $this->valueTypes($profile, $sliceParentId . ':' . $name);
            $properties = $this->properties($valueType, $name, $valueTypes);
            if ([] === $properties) {
                return null;
            }
            $resolved = false;
            foreach ($properties as $property) {
                $nested = [];
                $datatype = null;
                if ($complex) {
                    // a choice element is only complex for the alternative whose type has the nested fields
                    $nested = $this->children($profile, $nestedParentId, $property, $types);
                    if (null === $nested) {
                        continue;
                    }
                    $datatype = $this->datatype($profile, $nestedParentId);
                }
                $resolved = true;
                $children[] = new XverExtensionDefinition(
                    url: $name,
                    field: $property->getName(),
                    valueTypes: $valueTypes,
                    children: $nested,
                    datatype: $datatype,
                );
            }
            if (!$resolved) {
                return null;
            }
        }
        return $children;
    }

    private function datatype(Profile $profile, string $sliceParentId): null|string
    {
        $value = $profile->getElement($sliceParentId . ':_datatype.value[x]')?->getFixedValue()?->getValue();
        return is_string($value) && '' !== $value ? $value : null;
    }

    private function carriesValue(Profile $profile): bool
    {
        $value = $profile->getElement('Extension.value[x]');
        return null !== $value && !$value->isProhibited() && [] !== $value->getTypeCodes();
    }

    /** @return list<string> */
    private function valueTypes(Profile $profile, string $extensionId): array
    {
        $value = $profile->getElement($extensionId . '.value[x]');
        return null === $value || $value->isProhibited() ? [] : array_values(array_unique($value->getTypeCodes()));
    }

    /**
     * The field named by an extension path segment: the exact property, or for a choice element such as "value" every
     * concrete property that is the segment followed by a type name, e.g. "valueString" and "valueQuantity".
     *
     * An extension only carries the types the target version lacks, so a choice is limited to the alternatives named
     * by the extension's own value types.  Those are unknown ($valueTypes is empty) for a complex extension.
     *
     * @param list<string> $valueTypes
     * @return Property[]
     */
    private function properties(Type $holder, string $segment, array $valueTypes = []): array
    {
        $choices = [];
        foreach ($holder->getAllPropertiesIndexedIterator() as $property) {
            $name = $property->getName();
            if ($name === $segment) {
                return [$property];
            }
            $typeName = $property->getValueFHIRTypeName();
            if (null !== $typeName
                && $name === $segment . ucfirst($typeName)
                && ([] === $valueTypes || in_array($typeName, $valueTypes, true))) {
                $choices[] = $property;
            }
        }
        return $choices;
    }

    private function propertyType(Types $types, Property $property): null|Type
    {
        $name = $property->getValueFHIRTypeName();
        return $property->getValueFHIRType() ?? (null === $name ? null : $types->getTypeByName($name));
    }
}
