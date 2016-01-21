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
    /** @var array[] */
    private $_propertyTypes;

    /** @var string[] */
    private $_propertyObjectTypes;

    /** @var bool */
    private $_propertyIsCollection;

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

        $this->_propertyTypes = $propertyTemplate->getTypes();
        $this->_propertyObjectTypes = $propertyTemplate->getObjectTypes();
        $this->_propertyIsCollection = $propertyTemplate->isCollection();
    }

    /**
     * @return array
     */
    public function getPropertyTypes()
    {
        return $this->_propertyTypes;
    }

    /**
     * @return string[]
     */
    public function getPropertyObjectTypes()
    {
        return $this->_propertyObjectTypes;
    }

    /**
     * @return boolean
     */
    public function propertyIsCollection()
    {
        return $this->_propertyIsCollection;
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        $output = sprintf("    /**\n%s", $this->getDocBlockDocumentationFragment());

        if ($this->propertyIsCollection())
        {
            $output = sprintf(
                "%s     * @return %s[]\n",
                $output,
                implode('[]|', $this->getPropertyObjectTypes())
            );
        }
        else
        {
            $output = sprintf(
                "%s     * @return %s\n",
                $output,
                implode('[]', $this->getPropertyObjectTypes())
            );
        }

        $output = sprintf(
            "%s     */\n    %s function %s()\n    {\n",
            $output,
            (string)$this->getScope(),
            $this->getName()
        );

        foreach($this->getBody() as $line)
        {
            $output = sprintf("%s        %s\n", $output, $line);
        }

        return sprintf("%s    }\n\n", $output);
    }
}