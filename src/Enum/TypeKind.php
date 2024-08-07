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
enum TypeKind: string
{
    // this represents an actual value: string, int, etc.
    case PRIMITIVE = 'primitive';

    // these represent types that exist to wrap a primitive
    case PRIMITIVE_CONTAINER = 'primitive_container';

    // primitive type with limited possible value set
    case LIST = 'list';

    // complex types
    case BASE = 'Base';
    case EXTENSION          = 'Extension';
    case ELEMENT            = 'Element';
    case BINARY             = 'Binary';
    case BACKBONE_ELEMENT   = 'BackboneElement';
    case RESOURCE           = 'Resource';
    case RESOURCE_CONTAINER = 'ResourceContainer';
    case RESOURCE_INLINE    = 'Resource.Inline';
    case QUANTITY           = 'Quantity';

    // this indicates a type that is an immediate child of a resource and not used elsewhere
    case RESOURCE_COMPONENT = 'resource_component';

    // treated a bit different
    case PHPFHIR_XHTML = 'phpfhir_xhtml';

    private const VERSION_ROOT_KIND_MAP = [
        // DSTU1 has everything stem from "Element", lots of weird logic around this.
        'DSTU1' => [
            self::BINARY,
            self::ELEMENT,
            self::RESOURCE_INLINE,
        ],

        'DSTU2' => [
            self::ELEMENT,
            self::RESOURCE,
        ],
        'STU3' => [
            self::ELEMENT,
            self::RESOURCE,
        ],
        'R4' => [
            self::ELEMENT,
            self::RESOURCE,
        ],

        'R5' => [
            self::BASE,
        ],
    ];

    private const VERSION_CONTAINER_KIND_MAP = [
        'DSTU1' => self::RESOURCE_INLINE,
        'DSTU2' => self::RESOURCE_CONTAINER,
        'STU3' => self::RESOURCE_CONTAINER,
        'R4' => self::RESOURCE_CONTAINER,
        'R5' => self::RESOURCE_CONTAINER,
    ];

    /**
     * Returns the list of known "root" types for the given FHIR spec version
     *
     * @param string $version
     * @return \DCarbone\PHPFHIR\Enum\TypeKind[]
     */
    public static function versionRootTypes(string $version): array
    {
        return self::VERSION_ROOT_KIND_MAP[$version];
    }

    /**
     * Returns the TypeKind for the provided FHIR version
     *
     * @param string $version
     * @return \DCarbone\PHPFHIR\Enum\TypeKind
     */
    public static function versionContainerType(string $version): TypeKind
    {
        return self::VERSION_CONTAINER_KIND_MAP[$version];
    }

    /**
     * Returns true if the provided FHIR type name is a "root" type from which other types extend.
     *
     * @param string $version
     * @param string $fhirName
     * @return bool
     */
    public static function isRootTypeName(string $version, string $fhirName): bool
    {
        return in_array($fhirName, self::_rootTypesStrings($version), true);
    }

    /**
     * Returns true if the provided FHIR type name is the "container" type for the provided version.
     *
     * @param string $version
     * @param string $fhirName
     * @return bool
     */
    public static function isContainerTypeName(string $version, string $fhirName): bool
    {
        return self::_containerTypeString($version) === $fhirName;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKind|string ...$other
     * @return bool
     */
    public function isOneOf(TypeKind|string ...$other): bool
    {
        return in_array($this, $other, true) || in_array($this->value, $other, true);
    }

    /**
     * Returns true if this kind is the "container" kind for the provided FHIR version.
     *
     * @param string $version
     * @return bool
     */
    public function isContainer(string $version): bool
    {
        return $this === self::VERSION_CONTAINER_KIND_MAP[$version];
    }

    private static function _rootTypesStrings(string $version): array
    {
        return array_map(function (TypeKind $tk): string { return $tk->value; }, self::VERSION_ROOT_KIND_MAP[$version]);
    }

    private static function _containerTypeString(string $version): string
    {
        return self::VERSION_CONTAINER_KIND_MAP[$version]->value;
    }
}