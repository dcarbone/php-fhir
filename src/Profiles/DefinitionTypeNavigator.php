<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\ProfilePropertyKindEnum;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Property;

/**
 * {@see \DCarbone\PHPFHIR\Profiles\ProfileTypeNavigator} over the types a version's definition produced.
 */
class DefinitionTypeNavigator implements ProfileTypeNavigator
{
    /** @var array<string, array<string, Property>> */
    private array $_properties = [];
    private readonly Version $_version;

    public function __construct(Version $_version)
    {
        $this->_version = $_version;
    }

    public function getProperty(string $typeKey, string $field): null|ProfileProperty
    {
        $property = $this->_properties($typeKey)[$field] ?? null;
        return null === $property ? null : $this->_convert($property);
    }

    public function getChoiceProperties(string $typeKey, string $base): array
    {
        $out = [];
        $length = strlen($base);
        foreach ($this->_properties($typeKey) as $name => $property) {
            if (strlen($name) > $length && str_starts_with($name, $base) && ctype_upper($name[$length])) {
                $converted = $this->_convert($property);
                if (null !== $converted) {
                    $out[] = $converted;
                }
            }
        }
        return $out;
    }

    private function _properties(string $typeKey): array
    {
        if (!isset($this->_properties[$typeKey])) {
            $this->_properties[$typeKey] = [];
            $type = $this->_version->getDefinition()->getTypes()->getTypeByName($typeKey);
            if (null !== $type) {
                foreach ($type->getAllPropertiesIndexedIterator() as $property) {
                    $this->_properties[$typeKey][$property->getName()] = $property;
                }
            }
        }
        return $this->_properties[$typeKey];
    }

    private function _convert(Property $property): null|ProfileProperty
    {
        $type = $property->getValueFHIRType();
        if (null === $type || $type->getKind()->isResourceContainer($this->_version)) {
            return null;
        }
        $kind = match (true) {
            $type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent() => ProfilePropertyKindEnum::CONTAINER,
            $type->isPrimitiveType() => ProfilePropertyKindEnum::PRIMITIVE,
            default => ProfilePropertyKindEnum::COMPLEX,
        };
        return new ProfileProperty(
            $property->getName(),
            $property->getGetterName(),
            $property->isCollection(),
            $type->getFHIRName(),
            $type->getFullyQualifiedClassName(false),
            $kind,
        );
    }
}
