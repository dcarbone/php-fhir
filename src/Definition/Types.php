<?php

namespace DCarbone\PHPFHIR\Definition;

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

use DCarbone\PHPFHIR\Config;

/**
 * Class Types
 * @package DCarbone\PHPFHIR
 */
class Types implements \Iterator, \Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\Type[] */
    private $types = [];

    /** @var \DCarbone\PHPFHIR\Config */
    private $config;

    /**
     * FHIRTypes constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByFHIRName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getFHIRName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByClassName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getClassName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $fqn
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByFQN($fqn)
    {
        foreach ($this->types as $type) {
            if ($type->getFQN() === $fqn) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $group
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getTypesByGroup($group)
    {
        $types = [];
        foreach ($this->types as $type) {
            if ($type->getBaseType() === $group) {
                $types[] = $type;

            }
        }
        return $types;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return $this
     */
    public function addType(Type $type)
    {
        $tname = $type->getFHIRName();
        foreach ($this->types as $current) {
            if ($type === $current) {
                return $this;
            }
            if ($current->getFHIRName() === $tname) {
                throw new \LogicException(sprintf(
                    'Type %s is already defined',
                    $tname
                ));
            }
        }
        $this->types[] = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|false
     */
    public function current()
    {
        return current($this->types);
    }

    public function next()
    {
        next($this->types);
    }

    /**
     * @return int|null
     */
    public function key()
    {
        return key($this->types);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return null !== key($this->types);
    }

    public function rewind()
    {
        reset($this->types);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->types);
    }
}