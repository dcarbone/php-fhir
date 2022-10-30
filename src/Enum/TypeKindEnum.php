<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class TypeKindEnum
 * @package DCarbone\PHPFHIR\Enum
 */
class TypeKindEnum extends AbstractEnum
{
    // this represents an actual value: string, int, etc.
    public const PRIMITIVE = 'primitive';

    // these represent types that exist to wrap a primitive
    public const PRIMITIVE_CONTAINER = 'primitive_container';

    // primitive type with limited possible value set
    public const _LIST = 'list';

    // complex types
    public const EXTENSION          = 'Extension';
    public const ELEMENT            = 'Element';
    public const BACKBONE_ELEMENT   = 'BackboneElement';
    public const RESOURCE           = 'Resource';
    public const RESOURCE_CONTAINER = 'ResourceContainer';
    public const RESOURCE_INLINE    = 'Resource.Inline';
    public const QUANTITY           = 'Quantity';

    /** @var array */
    private const KNOWN_ROOTS = [
        self::EXTENSION,
        self::ELEMENT,
        self::BACKBONE_ELEMENT,
        self::RESOURCE,
        self::RESOURCE_CONTAINER,
        self::RESOURCE_INLINE,
    ];

    // this indicates a type that is an immediate child of a resource and not used elsewhere
    public const RESOURCE_COMPONENT = 'resource_component';

    // treated a bit different
    public const PHPFHIR_XHTML = 'phpfhir_xhtml';

    /**
     * @param $fhirName
     * @return bool
     */
    public static function isKnownRoot($fhirName): bool
    {
        return in_array($fhirName, self::KNOWN_ROOTS, true);
    }

    /**
     * @return bool
     */
    public function isPrimitive(): bool
    {
        return $this->is(TypeKindEnum::PRIMITIVE);
    }

    /**
     * @return bool
     */
    public function isPrimitiveContainer(): bool
    {
        return $this->is(TypeKindEnum::PRIMITIVE_CONTAINER);
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->is(self::_LIST);
    }

    /**
     * @return bool
     */
    public function isElement(): bool
    {
        return $this->is(self::ELEMENT);
    }

    /**
     * @return bool
     */
    public function isQuantity(): bool
    {
        return $this->is(self::QUANTITY);
    }

    /**
     * @return bool
     */
    public function isResource(): bool
    {
        return $this->is(self::RESOURCE);
    }

    /**
     * @return bool
     */
    public function isResourceContainer(): bool
    {
        return $this->is(self::RESOURCE_CONTAINER);
    }

    /**
     * @return bool
     */
    public function isInlineResource(): bool
    {
        return $this->is(self::RESOURCE_INLINE);
    }

    /**
     * @return bool
     */
    public function isPHPFHIRXHTML(): bool
    {
        return $this->is(self::PHPFHIR_XHTML);
    }
}