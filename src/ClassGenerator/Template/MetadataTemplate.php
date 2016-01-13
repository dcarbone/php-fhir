<?php namespace PHPFHIR\ClassGenerator\Template;

/**
 * Class MetadataTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class MetadataTemplate extends AbstractTemplate
{
    /** @var \DateTime */
    private $_date;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_date = new \DateTime('now');
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {

    }
}
