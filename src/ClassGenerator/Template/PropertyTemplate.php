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
 * Class PropertyTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class PropertyTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;

    /** @var PHPScopeEnum */
    protected $scope;

    /** @var bool */
    protected $isCollection = false;

    /** @var array  */
    protected $types = array();

    /**
     * @param string $name
     * @param PHPScopeEnum $scope
     * @param bool $isCollection
     */
    public function __construct($name, PHPScopeEnum $scope, $isCollection)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException(sprintf('Specified property name "%s" is not valid.', $name));

        $this->scope = $scope;
        $this->isCollection = $isCollection;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        return $this->isCollection;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param string $elementType
     * @param $objectType
     */
    public function addType($elementType, $objectType)
    {
        $this->types[$elementType] = $objectType;
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        $output = sprintf("    /**\n%s", $this->getDocBlockDocumentationFragment());

        if ($this->isCollection())
        {
            return sprintf("%s     * @var %s[]\n     */\n    %s %s = array();\n\n",
                $output,
                implode('|', $this->types),
                (string)$this->getScope(),
                NameUtils::getPropertyVariableName($this->getName())
            );
        }
        else
        {
            return sprintf("%s     * @var %s\n     */\n    %s %s;\n\n",
                $output,
                implode('|', $this->types),
                (string)$this->getScope(),
                NameUtils::getPropertyVariableName($this->getName())
            );
        }
    }
}