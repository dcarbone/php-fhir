<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\ParameterScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class ParameterTemplate
 * @package PHPFHIR\Template
 */
class ParameterTemplate
{
    /** @var string */
    protected $name;
    /** @var ParameterScopeEnum */
    protected $scope;
    /** @var string */
    protected $varType;
    /** @var string */
    protected $documentation;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $scope
     * @param string $varType
     * @param null|string $documentation
     */
    public function __construct($name, $scope, $varType, $documentation = null)
    {
        $this->scope = new ParameterScopeEnum($scope);

        if (NameUtils::isValidVariableName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Specified parameter name "'.$name.'" is not valid.');

        $this->varType = $varType;
        $this->documentation = $documentation;
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
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function getVarType()
    {
        return $this->varType;
    }

    /**
     * @return string
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $output = "    /**\n";

        if ($this->documentation)
            $output = sprintf("%s     * %s\n", $this->documentation);

        return <<<STRING
{$output}
     * @var {$this->varType}
     */
    {$this->scope} \${$this->name};
STRING;
    }
}