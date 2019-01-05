<?php

namespace DCarbone\PHPFHIR\Definition\Type;

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

use DCarbone\PHPFHIR\Definition\Type\EnumerationValue;

/**
 * Class Enumeration
 * @package DCarbone\PHPFHIR\Definition\Type
 */
class Enumeration implements \Iterator, \Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\Type\EnumerationValue[] */
    private $values = [];

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type\EnumerationValue $value
     * @return \DCarbone\PHPFHIR\Definition\Type\Enumeration
     */
    public function addValue(EnumerationValue $value)
    {
        $eval = $value->getValue();
        foreach ($this->values as $cval) {
            if ($value === $cval) {
                return $this;
            }
            if ($cval->getValue() === $eval) {
                throw new \LogicException(sprintf(
                    'Enum already has value %s',
                    $value
                ));
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
     * @param \DCarbone\PHPFHIR\Definition\Type\EnumerationValue $value
     * @return bool
     */
    public function hasValue(EnumerationValue $value)
    {
        return in_array($value, $this->values, true);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type\EnumerationValue|false
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