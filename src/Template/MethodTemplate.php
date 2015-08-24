<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class MethodTemplate
 * @package PHPFHIR\Template
 */
abstract class MethodTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;

    /** @var PHPScopeEnum */
    protected $scope;

    /** @var array */
    protected $body = array();

    /**
     * Constructor
     *
     * @param string $name
     * @param PHPScopeEnum $scope
     */
    public function __construct($name, PHPScopeEnum $scope)
    {
        if (NameUtils::isValidFunctionName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Function name "'.$name.'" is not valid.');

        $this->scope = $scope;
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
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $body
     */
    public function setBody(array $body)
    {
        $this->body = $body;
    }

    /**
     * @param string $line
     */
    public function addLineToBody($line)
    {
        $this->body[] = $line;
    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    abstract public function __toString();
}