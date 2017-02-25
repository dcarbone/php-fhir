<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\Method;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DCarbone\PHPFHIR\ClassGenerator\Enum\PHPScopeEnum;
use DCarbone\PHPFHIR\ClassGenerator\Template\AbstractTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\BaseParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class BaseMethodTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class BaseMethodTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;

    /** @var PHPScopeEnum */
    protected $scope;

    /** @var \DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\BaseParameterTemplate[] */
    protected $parameters = array();

    /** @var null|string */
    protected $returnValueType = null;
    /** @var null|string */
    protected $returnStatement = null;

    /** @var array */
    protected $body = array();

    /**
     * Constructor
     *
     * @param string $name
     * @param PHPScopeEnum $scope
     */
    public function __construct($name, PHPScopeEnum $scope = null)
    {
        if (NameUtils::isValidFunctionName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Function name "'.$name.'" is not valid.');

        if (null === $scope)
            $this->scope = new PHPScopeEnum(PHPScopeEnum::_PUBLIC);
        else
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
     * @return null|string
     */
    public function getReturnValueType()
    {
        return $this->returnValueType;
    }

    /**
     * @param null|string $returnValueType
     */
    public function setReturnValueType($returnValueType)
    {
        $this->returnValueType = $returnValueType;
    }

    /**
     * @return null|string
     */
    public function getReturnStatement()
    {
        return $this->returnStatement;
    }

    /**
     * @param null|string $returnStatement
     */
    public function setReturnStatement($returnStatement)
    {
        $this->returnStatement = $returnStatement;
    }

    /**
     * @return BaseParameterTemplate[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param BaseParameterTemplate $parameterTemplate
     */
    public function addParameter(BaseParameterTemplate $parameterTemplate)
    {
        $this->parameters[$parameterTemplate->getName()] = $parameterTemplate;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * @param string $name
     * @return BaseParameterTemplate
     */
    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return sprintf('%s%s', $this->buildDocBlock(), $this->buildMethodDefinition());
    }

    /**
     * @return string
     */
    protected function buildDocBlock()
    {
        $output = sprintf("    /**\n%s", $this->getDocBlockDocumentationFragment());

        foreach($this->getParameters() as $param)
        {
            $output = sprintf(
                "%s     * %s\n",
                $output,
                $param->getParamDocBlockFragment()
            );
        }

        return sprintf("%s%s     */\n", $output, $this->buildReturnDocBlockStatement());
    }

    /**
     * @return string
     */
    protected function buildMethodDefinition()
    {
        return sprintf(
            "    %s function %s(%s)\n    {\n%s    }\n\n",
            (string)$this->getScope(),
            $this->getName(),
            $this->buildMethodParameterDefinition(),
            $this->buildMethodBody()
        );
    }

    /**
     * @return string
     */
    protected function buildMethodParameterDefinition()
    {
        $output = '';
        $params = array();
        foreach($this->getParameters() as $param)
        {
            $params[] = $param->compileTemplate();
        }

        return sprintf('%s%s', $output, implode(', ', $params));
    }

    /**
     * @return string
     */
    protected function buildMethodBody()
    {
        $output = '';
        foreach($this->getBody() as $line)
        {
            $output = sprintf("%s        %s\n", $output, $line);
        }

        return sprintf('%s%s', $output, $this->buildMethodReturnStatement());
    }

    /**
     * @return string
     */
    protected function buildReturnDocBlockStatement()
    {
        if (is_string($this->returnValueType))
            return sprintf("     * @return %s\n", $this->returnValueType);

        return '';
    }

    /**
     * @return string
     */
    protected function buildMethodReturnStatement()
    {
        if (is_string($this->returnStatement))
            return sprintf("        return %s;\n", $this->returnStatement);

        return '';
    }
}