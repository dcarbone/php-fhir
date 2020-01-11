<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Class Enumeration
 * @package DCarbone\PHPFHIR\Definition
 */
class Enumeration implements \Iterator, \Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\EnumerationValue[] */
    private $values = [];
    /** @var \DCarbone\PHPFHIR\Definition\Type */
    private $type;

    /**
     * Enumeration constructor.
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\EnumerationValue $value
     * @return \DCarbone\PHPFHIR\Definition\Enumeration
     */
    public function addValue(EnumerationValue $value)
    {
        $eval = $value->getValue();
        foreach ($this->values as $cval) {
            if ($value === $cval || $cval->getValue() === $eval) {
                return $this;
            }
        }
        $this->values[] = $value;
        return $this;
    }

    /**
     * @param mixed $rawValue
     * @return bool
     */
    public function hasRawValue($rawValue)
    {
        foreach ($this->values as $value) {
            if ($value->getValue() === $rawValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\EnumerationValue $value
     * @return bool
     */
    public function hasValue(EnumerationValue $value)
    {
        return in_array($value, $this->values, true);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\EnumerationValue|false
     */
    public function current()
    {
        return current($this->values);
    }

    public function next()
    {
        next($this->values);
    }

    /**
     * @return int|null
     */
    public function key()
    {
        return key($this->values);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return null !== key($this->values);
    }

    public function rewind()
    {
        reset($this->values);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->values);
    }
}