<?php namespace PHPFHIR\Template;

use PHPFHIR\Utilities\NameUtils;

/**
 * Class ParameterTemplate
 * @package PHPFHIR\Template
 */
class ParameterTemplate
{
    /** @var string */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Specified parameter name "'.$name.'" is not valid.');
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
    public function getParamDocBlock()
    {
        return sprintf('@param %s', NameUtils::getPropertyVariableName($this->getName()));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return NameUtils::getPropertyVariableName($this->getName());
    }
}