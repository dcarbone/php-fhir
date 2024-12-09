<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

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

use DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue;

/**
 * Class Enumeration
 * @package DCarbone\PHPFHIR\Definition
 */
class Enumeration implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue[] */
    private array $values = [];

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue $value
     * @return \DCarbone\PHPFHIR\Version\Definition\Enumeration
     */
    public function addValue(EnumerationValue $value): Enumeration
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
    public function hasRawValue(mixed $rawValue): bool
    {
        foreach ($this->values as $value) {
            if ($value->getValue() === $rawValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue $value
     * @return bool
     */
    public function hasValue(EnumerationValue $value): bool
    {
        return in_array($value, $this->values, true);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue[]
     */
    public function getIterator(): iterable
    {
        return \SplFixedArray::fromArray($this->values);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->values);
    }
}