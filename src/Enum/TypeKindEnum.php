<?php

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    const PRIMITIVE = 'primitive';

    // these represent types that exist to wrap a primitive
    const PRIMITIVE_CONTAINER = 'primitive_container';

    // primitive type with limited possible value set
    const _LIST = 'list';

    // complex types
    const EXTENSION          = 'Extension';
    const ELEMENT            = 'Element';
    const BACKBONE_ELEMENT   = 'BackboneElement';
    const RESOURCE           = 'Resource';
    const RESOURCE_CONTAINER = 'ResourceContainer';
    const RESOURCE_INLINE    = 'Resource.Inline';

    /** @var array */
    private static $knownRoots = [
        self::EXTENSION,
        self::ELEMENT,
        self::BACKBONE_ELEMENT,
        self::RESOURCE,
        self::RESOURCE_CONTAINER,
        self::RESOURCE_INLINE,
    ];

    // this indicates a type that is an immediate child of a resource and not used elsewhere
    const RESOURCE_COMPONENT = 'resource_component';

    // the generic type is applied to anything that is not a child of a known root or a known root itself.
    const GENERIC = 'generic';

    /**
     * @param $fhirName
     * @return bool
     */
    public static function isKnownRoot($fhirName)
    {
        return in_array($fhirName, self::$knownRoots, true);
    }

    /**
     * @return bool
     */
    public function isPrimitive()
    {
        return $this->is(TypeKindEnum::PRIMITIVE);
    }

    /**
     * @return bool
     */
    public function isPrimitiveContainer()
    {
        return $this->is(TypeKindEnum::PRIMITIVE_CONTAINER);
    }

    /**
     * @return bool
     */
    public function isList()
    {
        return $this->is(self::_LIST);
    }

    /**
     * @return bool
     */
    public function isElement()
    {
        return $this->is(self::ELEMENT);
    }

    /**
     * @return bool
     */
    public function isResource()
    {
        return $this->is(self::RESOURCE);
    }

    /**
     * @return bool
     */
    public function isResourceContainer()
    {
        return $this->is(self::RESOURCE_CONTAINER);
    }

    /**
     * @return bool
     */
    public function isInlineResource()
    {
        return $this->is(self::RESOURCE_INLINE);
    }

    /**
     * @return bool
     */
    public function isGeneric()
    {
        return $this->is(self::GENERIC);
    }
}