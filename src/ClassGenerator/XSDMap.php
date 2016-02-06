<?php namespace DCarbone\PHPFHIR\ClassGenerator;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * @package PHPFHIR
 */
class XSDMap implements \ArrayAccess, \Iterator
{
    /** @var \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry[] */
    private $_map = array();

    /**
     * @param string $fhirElementName
     * @return string|null
     */
    public function getClassNameForFHIRElementName($fhirElementName)
    {
        if (isset($this[$fhirElementName]))
            return $this[$fhirElementName]->className;

        return null;
    }

    /**
     * @param string $fhirElementName
     * @return null|string
     */
    public function getClassUseStatementForFHIRElementName($fhirElementName)
    {
        if (isset($this[$fhirElementName]))
            return sprintf('%s\\%s', $this[$fhirElementName]->namespace, $this[$fhirElementName]->className);

        return null;
    }


    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->_map);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->_map);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->_map);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * @since 5.0.0
     */
    public function valid()
    {
        return null !== key($this->_map);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->_map);
    }

    /**
     * @return mixed
     */
    public function reset()
    {
        return reset($this->_map);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->_map[$offset]) || array_key_exists($offset, $this->_map);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if (isset($this->_map[$offset]) || array_key_exists($offset, $this->_map))
            return $this->_map[$offset];

        throw new \OutOfBoundsException(sprintf(
            '%s - No such offset exists in this map.',
            get_class($this)
        ));
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset)
            $this->_map[] = $value;
        else
            $this->_map[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(sprintf(
            '%s - Cannot unset entry in map.',
            get_class($this)
        ));
    }
}