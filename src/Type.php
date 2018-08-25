<?php

namespace DCarbone\PHPFHIR;

/**
 * Class Type
 * @package DCarbone\PHPFHIR
 */
class Type
{
    const BASE_TYPE_ELEMENT          = 'Element';
    const BASE_TYPE_BACKBONE_ELEMENT = 'BackboneElement';
    const BASE_TYPE_RESOURCE         = 'Resource';
    const BASE_TYPE_DOMAIN_RESOURCE  = 'DomainResource';
    const BASE_TYPE_QUANTITY         = 'Quantity';

    /** @var \DCarbone\PHPFHIR\Config */
    private $config;
    /** @var \SimpleXMLElement */
    private $sourceSXE;

    /** @var string */
    private $name;
    /** @var null|string */
    private $baseType = null;

    /** @var bool */
    private $component = false;

    /** @var string */
    private $namespace;
    /** @var string */
    private $className;

    /** @var null|\DCarbone\PHPFHIR\Type */
    private $extendedType = null;

    /** @var */
    private $properties = [];

    /**
     * FHIRType constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \SimpleXMLElement $sourceSXE
     * @param string $group
     * @param string $name
     */
    public function __construct(Config $config, \SimpleXMLElement $sourceSXE, $group, $name)
    {
        $this->config = $config;
        $this->sourceSXE = clone $sourceSXE;
        $this->baseType = $group;
        $this->name = $name;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getSourceSXE()
    {
        return clone $this->sourceSXE;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getBaseType()
    {
        return $this->baseType;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * @return string
     */
    public function getFQN()
    {
        return "{$this->namespace}\\{$this->className}";
    }

    /**
     * @return \DCarbone\PHPFHIR\Type|null
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * Set the Type this Type inherits from
     *
     * @param \DCarbone\PHPFHIR\Type|null $extendedType
     * @return $this
     */
    public function setExtendedType(Type $extendedType)
    {
        $this->extendedType = $extendedType;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}