<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class MethodTemplate
 * @package PHPFHIR\Template
 */
class MethodTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;
    /** @var PHPScopeEnum */
    protected $scope;
    /** @var ParameterTemplate[] */
    protected $parameters = array();
    /** @var array */
    protected $body;

    /**
     * Constructor
     *
     * @param string $name
     * @param PHPScopeEnum $scope
     * @param array $body
     */
    public function __construct($name, PHPScopeEnum $scope, array $body = array())
    {
        if (NameUtils::isValidFunctionName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Function name "'.$name.'" is not valid.');

        $this->body = $body;
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
     * @return ParameterTemplate[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param ParameterTemplate $parameterTemplate
     */
    public function addParameter(ParameterTemplate $parameterTemplate)
    {
        $this->parameters[] = $parameterTemplate;
    }

    /**
     * @return string
     */
    public function getReturnType()
    {
        return $this->returnType;
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

    public function __toString()
    {
        $output = "    /**\n";
//        if (isset($this->documentation))
//        {
//            foreach($this->documentation as $doc)
//            {
//                $output = sprintf("%s     * %s\n", $output, $doc);
//            }
//        }

        foreach($this->getParameters() as $param)
        {
            $output = sprintf(
                "%s     * @param %s\n",
                $output,
                NameUtils::getPropertyVariableName($param->getName())
            );
        }

        if (isset($this->returnType))
            $output = sprintf("%s     * @return %s\n", $output, (string)$this->returnType);

        $output = sprintf("%s     */\n    %s function %s(", $output, (string)$this->getScope(), $this->getName());

        $params = array();
        foreach($this->getParameters() as $param)
        {
            $params[] = NameUtils::getPropertyVariableName($param->getName());
        }
        $output = sprintf("%s%s)\n    {\n", $output, implode(', ', $params));

        foreach($this->getBody() as $line)
        {
            $output = sprintf("%s        %s\n", $output, $line);
        }

        return sprintf("%s    }\n\n", $output);
    }
}