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
    use ValuesTrait;

    // this represents an actual value: string, int, etc.
    case PRIMITIVE = 'primitive';

    // these represent types that exist to wrap a primitive
    case PRIMITIVE_CONTAINER = 'primitive_container';

    // primitive type with limited possible value set
    case _LIST = 'list';

    // complex types
    case EXTENSION          = 'Extension';
    case ELEMENT            = 'Element';
    case BACKBONE_ELEMENT   = 'BackboneElement';
    case RESOURCE           = 'Resource';
    case RESOURCE_CONTAINER = 'ResourceContainer';
    case RESOURCE_INLINE    = 'Resource.Inline';
    case QUANTITY           = 'Quantity';

    // this indicates a type that is an immediate child of a resource and not used elsewhere
    case RESOURCE_COMPONENT = 'resource_component';

    // treated a bit different
    case PHPFHIR_XHTML = 'phpfhir_xhtml';

    private const KNOWN_ROOTS = [
        self::EXTENSION,
        self::ELEMENT,
        self::BACKBONE_ELEMENT,
        self::RESOURCE,
        self::RESOURCE_CONTAINER,
        self::RESOURCE_INLINE,
    ];

    /**
     * @param string $fhirName
     * @return bool
     */
    public static function isKnownRoot(string $fhirName): bool
    {
        $rootStrings = array_map(function (TypeKind $tk): string { return $tk->value; }, self::KNOWN_ROOTS);
        return in_array($fhirName, $rootStrings, true);
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKind|string ...$other
     * @return bool
     */
    public function isOneOf(TypeKind|string ...$other): bool
    {
        $vals = self::values();
        foreach ($other as $name) {
            if ($this === $name || in_array($name, $vals, true)) {
                return true;
            }
        }

        return false;
    }
}