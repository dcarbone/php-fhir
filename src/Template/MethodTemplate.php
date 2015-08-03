<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\MethodScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class MethodTemplate
 * @package PHPFHIR\Template
 */
class MethodTemplate
{
    /** @var string */
    protected $name;
    /** @var MethodScopeEnum */
    protected $scope;
    /** @var array */
    protected $parameters = array();
    /** @var string */
    protected $returnType;
    /** @var string */
    protected $body;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $returnType
     * @param string $body
     */
    public function __construct($name, $returnType, $body)
    {
        $this->scope = new MethodScopeEnum($name);

        if (NameUtils::isValidFunctionName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Function name "'.$name.'" is not valid.');

        $this->returnType = $returnType;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    public function __toString()
    {

    }
}