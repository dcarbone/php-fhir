<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

enum TypeKindEnum: string
{
    // this represents an actual value: string, int, etc.
    case PRIMITIVE = 'primitive';

    // these represent types that exist to wrap a primitive
    case PRIMITIVE_CONTAINER = 'primitive_container';

    // primitive type with limited possible value set
    case LIST = 'list';

    // complex types
    case BASE = 'Base';
    case EXTENSION = 'Extension';
    case ELEMENT = 'Element';
    case BINARY = 'Binary';
    case BACKBONE_ELEMENT = 'BackboneElement';
    case RESOURCE = 'Resource';
    case RESOURCE_CONTAINER = 'ResourceContainer';
    case RESOURCE_INLINE = 'Resource.Inline';
    case QUANTITY = 'Quantity';

    // this indicates a type that is an immediate child of a resource and not used elsewhere
    case RESOURCE_COMPONENT = 'resource_component';

    // treated a bit different
    case PHPFHIR_XHTML = 'phpfhir_xhtml';

    private const CONTAINER_TYPES = [
        self::RESOURCE_CONTAINER,
        self::RESOURCE_INLINE,
    ];

    /**
     * Returns true if the provided FHIR type name is the "container" type for the provided version.
     *
     * @param string $fhirName
     * @return bool
     */
    public static function isContainerTypeName(string $fhirName): bool
    {
        return in_array($fhirName, self::_containerTypeStrings(), true);
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum|string ...$other
     * @return bool
     */
    public function isOneOf(TypeKindEnum|string ...$other): bool
    {
        return in_array($this, $other, true) || in_array($this->value, $other, true);
    }

    /**
     * Returns true if this kind is the "container" kind for the provided FHIR version.
     *
     * @return bool
     */
    public function isContainer(): bool
    {
        return in_array($this, self::CONTAINER_TYPES, true);
    }

    private static function _containerTypeStrings(): array
    {
        return array_map(function (TypeKindEnum $tk): string {
            return $tk->value;
        }, self::CONTAINER_TYPES);
    }
}