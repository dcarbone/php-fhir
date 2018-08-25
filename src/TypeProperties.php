<?php

namespace DCarbone\PHPFHIR;

/**
 * Class TypeProperties
 * @package DCarbone\PHPFHIR
 */
class TypeProperties implements \Iterator, \Countable
{
    /** @var \DCarbone\PHPFHIR\TypeProperty[] */
    private $properties = [];

    /** @var */
    private $config;

    /** @var \DCarbone\PHPFHIR\Type; */
    private $type;

    /**
     * TypeProperties constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Type $type
     */
    public function __construct(Config $config, Type $type)
    {
        $this->config = $config;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \DCarbone\PHPFHIR\TypeProperty $property
     * @return $this
     */
    public function addProperty(TypeProperty $property)
    {
        $pname = $property->getName();
        foreach ($this->properties as $current) {
            if ($pname === $current->getName()) {
                throw new \LogicException(sprintf(
                    'Duplicate Type %s property %s seen',
                    $this->getType()->getName(),
                    $property->getName()
                ));
            }
        }
        $this->properties[] = $property;
        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\TypeProperty|null
     */
    public function getProperty($name)
    {
        foreach ($this->properties as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return null !== $this->getProperty($name);
    }

    /**
     * @return \DCarbone\PHPFHIR\TypeProperty|null
     */
    public function current()
    {
        return current($this->properties);
    }

    public function next()
    {
        next($this->properties);
    }

    /**
     * @return int|null
     */
    public function key()
    {
        return key($this->properties);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return null !== key($this->properties);
    }

    public function rewind()
    {
        reset($this->properties);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->properties);
    }
}