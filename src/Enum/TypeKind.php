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
    // this represents an actual value: string, int, etc.
    const PRIMITIVE = 'primitive';

    // this is a special type that is, in effect, an enumeration of possible values for the value property on this type
    const _LIST = 'list';

    // date types are treated special
    const DATE      = 'date';
    const DATE_TIME = 'dateTime';
    const TIME      = 'time';
    const INSTANT   = 'instant';

    // these describe objects
    const ELEMENT            = 'Element';
    const RESOURCE           = 'Resource';
    const RESOURCE_CONTAINER = 'ResourceContainer';
    const RESOURCE_INLINE    = 'Resource.Inline';
    const DOMAIN_RESOURCE    = 'DomainResource';

    // these are special types that are used to represent the values of types that have special handling
    const DATE_VALUE      = 'date_value';
    const DATE_TIME_VALUE = 'dateTime_value';
    const TIME_VALUE      = 'time_value';
    const INSTANT_VALUE   = 'instant_value';
    const HTML_VALUE      = 'html_value';
    const LIST_VALUE      = 'list_value';
    const PRIMITIVE_VALUE = 'primitive_value';

    // if this is seen, it means something referenced a type that was not defined anywhere in the xsd's
    const UNDEFINED = 'UNDEFINED';

    /**
     * Creates a new value of some type
     *
     * @param string $value
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct($value)
    {
        if (!is_string($value)) {
            parent::__construct($value);
        } else {
            $len = strlen($value);
            if ($len > 10 && '-primitive' === substr($value, -10)) {
                parent::__construct(self::PRIMITIVE_VALUE);
            } elseif ($len > 5 && '-list' === substr($value, -5)) {
                parent::__construct(self::LIST_VALUE);
            } else {
                // first, test for primitive type
                try {
                    new PrimitiveType($value);
                    parent::__construct(self::PRIMITIVE);
                } catch (\UnexpectedValueException $e) {
                    // finally, pass on through to parent
                    parent::__construct($value);
                }
            }
        }
    }

    /**
     * @param string $kind
     * @return bool
     */
    public function is($kind)
    {
        return is_string($kind) && $kind === (string)$this;
    }

    /**
     * @param array $kinds
     * @return bool
     */
    public function isOneOf(array $kinds)
    {
        foreach ($kinds as $kind) {
            if ($this->is($kind)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isPrimitive()
    {
        return $this->is(TypeKind::PRIMITIVE);
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
    public function isCode()
    {
        return $this->is(self::CODE);
    }

    /**
     * @return bool
     */
    public function isTypeOfDate()
    {
        return $this->isOneOf([
            self::DATE,
            self::DATE_TIME,
            self::TIME,
            self::INSTANT,
        ]);
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
    public function isDomainResource()
    {
        return $this->is(self::DOMAIN_RESOURCE);
    }

    /**
     * @return bool
     */
    public function isTypeValue()
    {
        return $this->isOneOf([
            self::DATE_VALUE,
            self::DATE_TIME_VALUE,
            self::TIME_VALUE,
            self::INSTANT_VALUE,
            self::HTML_VALUE,
            self::LIST_VALUE,
            self::PRIMITIVE_VALUE,
        ]);
    }

    /**
     * @return bool
     */
    public function isUndefined()
    {
        return $this->is(self::UNDEFINED);
    }
}