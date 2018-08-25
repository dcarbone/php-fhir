<?php

namespace DCarbone\PHPFHIR;

/**
 * Class TypeProperty
 * @package DCarbone\PHPFHIR
 */
class TypeProperty
{
    /** @var \DCarbone\PHPFHIR\Config */
    private $config;

    /** @var string */
    private $name;

    /**
     * Property constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param $name
     */
    public function __construct(Config $config, $name)
    {
        $this->config = $config;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}