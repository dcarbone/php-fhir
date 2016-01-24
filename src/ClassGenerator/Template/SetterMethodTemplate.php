<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template;

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
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class SetterMethodTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class SetterMethodTemplate extends AbstractMethodTemplate
{
    /** @var ParameterTemplate[] */
    protected $parameters = array();

    /**
     * Constructor
     *
     * @param PropertyTemplate $propertyTemplate
     */
    public function __construct(PropertyTemplate $propertyTemplate)
    {
        if ($propertyTemplate->isCollection())
            $name = sprintf('add%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));
        else
            $name = sprintf('set%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));

        parent::__construct($name, new PHPScopeEnum(PHPScopeEnum::_PUBLIC));

        $this->setDocumentation($propertyTemplate->getDocumentation());
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
    public function compileTemplate()
    {
        $output = sprintf("    /**\n%s", $this->getDocBlockDocumentationFragment());

        foreach($this->getParameters() as $param)
        {
            $output = sprintf(
                "%s     * %s\n",
                $output,
                $param->getParamDocBlockFragment(true)
            );
        }

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