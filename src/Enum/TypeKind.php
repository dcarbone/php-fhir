<?php

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use MyCLabs\Enum\Enum;

/**
 * Class TypeKind
 * @package DCarbone\PHPFHIR\Enum
 */
class TypeKind extends Enum
{
    // these represent typical types
    const PRIMITIVE          = 'Primitive';
    const _LIST              = 'List';
    const ELEMENT            = 'Element';
    const RESOURCE           = 'Resource';
    const RESOURCE_CONTAINER = 'ResourceContainer';
    const RESOURCE_INLINE    = 'Resource.Inline';
    const DOMAIN_RESOURCE    = 'DomainResource';

    // these are special types that are used to represent the values of types that have special handling
    const HTML_VALUE      = 'htmlValue';
    const LIST_VALUE      = 'listValue';
    const PRIMITIVE_VALUE = 'primitiveValue';

    // if this is seen, it means something referenced a type that was not defined anywhere in the
    // xsd's
    const UNDEFINED = 'UNDEFINED';

    /**
     * @return bool
     */
    public function isPrimitive()
    {
        return self::PRIMITIVE === (string)$this;
    }

    /**
     * @return bool
     */
    public function isList()
    {
        return self::_LIST === (string)$this;
    }

    /**
     * @return bool
     */
    public function isElement()
    {
        return self::ELEMENT === (string)$this;
    }

    /**
     * @return bool
     */
    public function isResource()
    {
        return self::RESOURCE === (string)$this;
    }

    /**
     * @return bool
     */
    public function isResourceContainer()
    {
        return self::RESOURCE_CONTAINER === (string)$this;
    }

    /**
     * @return bool
     */
    public function isResourceInline()
    {
        return self::RESOURCE_INLINE === (string)$this;
    }

    /**
     * @return bool
     */
    public function isDomainResource()
    {
        return self::DOMAIN_RESOURCE === (string)$this;
    }

    /**
     * @return bool
     */
    public function isValue()
    {
        switch ((string)$this) {
            case self::HTML_VALUE:
            case self::LIST_VALUE:
            case self::PRIMITIVE:
                return true;
            default:
                return false;
        }
    }

    /**
     * @return bool
     */
    public function isUndefined()
    {
        return self::UNDEFINED === (string)$this;
    }
}