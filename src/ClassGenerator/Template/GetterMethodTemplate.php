<?php namespace PHPFHIR\ClassGenerator\Template;

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

use PHPFHIR\ClassGenerator\Enum\PHPScopeEnum;
use PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class GetterMethodTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class GetterMethodTemplate extends AbstractMethodTemplate
{
    /** @var array */
    protected $propertyTypes;

    /** @var bool */
    protected $propertyIsCollection;

    /**
     * Constructor
     *
     * @param PropertyTemplate $propertyTemplate
     */
    public function __construct(PropertyTemplate $propertyTemplate)
    {
        $name = sprintf('get%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));

        parent::__construct($name, new PHPScopeEnum(PHPScopeEnum::_PUBLIC));

        $this->setDocumentation($propertyTemplate->getDocumentation());
        $this->propertyTypes = $propertyTemplate->getTypes();
        $this->propertyIsCollection = $propertyTemplate->isCollection();
    }

    /**
     * @return array
     */
    public function getPropertyTypes()
    {
        return $this->propertyTypes;
    }

    /**
     * @return boolean
     */
    public function propertyIsCollection()
    {
        return $this->propertyIsCollection;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $output = sprintf("    /**\n%s", self::_getDocumentationOutput());

        if ($this->propertyIsCollection())
            $output = sprintf("%s     * @return %s[]\n", $output, implode('[]|', $this->getPropertyTypes()));
        else
            $output = sprintf("%s     * @return %s\n", $output, implode('[]', $this->getPropertyTypes()));

        $output = sprintf("%s     */\n    %s function %s()\n    {\n", $output, (string)$this->getScope(), $this->getName());

        foreach($this->getBody() as $line)
        {
            $output = sprintf("%s        %s\n", $output, $line);
        }

        return sprintf("%s    }\n\n", $output);
    }
}