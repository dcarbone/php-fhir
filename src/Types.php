<?php

namespace DCarbone\PHPFHIR;

/**
 * Class Types
 * @package DCarbone\PHPFHIR
 */
class Types implements \Iterator, \Countable
{
    /** @var \DCarbone\PHPFHIR\Type[] */
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
     * @return \DCarbone\PHPFHIR\Type|null
     */
    public function getTypeByName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $group
     * @return \DCarbone\PHPFHIR\Type[]
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
     * @return \DCarbone\PHPFHIR\Type|false
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