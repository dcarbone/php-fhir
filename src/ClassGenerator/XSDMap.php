<?php namespace DCarbone\PHPFHIR\ClassGenerator;

/*
 * Copyright 2016-2017 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Class XSDMap
 * @package DCarbone\PHPFHIR\ClassGenerator
 */
class XSDMap implements \ArrayAccess, \Iterator {
    /** @var \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry[] */
    private $map = [];

    /**
     * @param string $fhirElementName
     * @return string|null
     */
    public function getClassNameForFHIRElementName($fhirElementName) {
        if (isset($this[$fhirElementName])) {
            return $this[$fhirElementName]->className;
        }

        return null;
    }

    /**
     * @param string $fhirElementName
     * @return null|string
     */
    public function getClassUseStatementForFHIRElementName($fhirElementName) {
        if (isset($this[$fhirElementName])) {
            return sprintf('%s\\%s', $this[$fhirElementName]->namespace, $this[$fhirElementName]->className);
        }

        return null;
    }


    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry|mixed
     */
    public function current() {
        return current($this->map);
    }

    public function next() {
        next($this->map);
    }

    /**
     * @return string|null
     */
    public function key() {
        return key($this->map);
    }

    /**
     * @return bool
     */
    public function valid() {
        return null !== key($this->map);
    }

    public function rewind() {
        reset($this->map);
    }

    /**
     * @return mixed
     */
    public function reset() {
        return reset($this->map);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->map[$offset]) || array_key_exists($offset, $this->map);
    }

    /**
     * @param mixed $offset
     * @return \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry
     */
    public function offsetGet($offset) {
        if (isset($this->map[$offset]) || array_key_exists($offset, $this->map)) {
            return $this->map[$offset];
        }

        throw new \OutOfBoundsException(sprintf(
            '%s - No such offset exists in this map.',
            get_class($this)
        ));
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (null === $offset) {
            $this->map[] = $value;
        } else {
            $this->map[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        throw new \BadMethodCallException(sprintf(
            '%s - Cannot unset entry in map.',
            get_class($this)
        ));
    }
}