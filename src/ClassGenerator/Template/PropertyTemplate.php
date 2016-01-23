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

    /** @var mixed */
    protected $defaultValue;

    /**
     * Constructor
     *
     * @param string $name
     * @param PHPScopeEnum $scope
     * @param bool $isCollection
     * @param null $defaultValue
     */
    public function __construct($name, PHPScopeEnum $scope, $isCollection, $defaultValue = null)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException(sprintf('Specified property name "%s" is not valid.', $name));

        $this->scope = $scope;
        $this->isCollection = $isCollection;
        $this->defaultValue = $defaultValue;
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
     * @param mixed $value
     */
    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
    }

    /**
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
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
     * @param string $elementName
     * @param string $objectClassName
     * @param string $objectElementName
     */
    public function addType($elementName, $objectClassName, $objectElementName)
    {
        $this->types[$elementName] = array(
            'className' => $objectClassName,
            'elementName' => $objectElementName
        );
    }

    /**
     * @return string[]
     */
    public function getObjectTypes()
    {
        $objects = array();
        foreach($this->getTypes() as $type)
        {
            $objects[] = $type['className'];
        }
        return $objects;
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return sprintf(
            "    /**\n%s     * @var %s%s\n     */\n    %s %s = %s;\n\n",
            $this->getDocBlockDocumentationFragment(),
            implode('|', $this->getObjectTypes()),
            ($this->isCollection() ? '[]' : ''),
            (string)$this->getScope(),
            NameUtils::getPropertyVariableName($this->getName()),
            ($this->isCollection() ? 'array()' : $this->determineDefaultValueOutput())
        );
    }

    /**
     * @return mixed|null|string
     */
    protected function determineDefaultValueOutput()
    {
        $default = $this->getDefaultValue();
        switch(gettype($default))
        {
            case 'string':
            case 'integer':
            case 'double':
            case 'float':
                return $default;

            case 'boolean':
                return ($default ? 'true' : 'false');

            case 'array':
                return var_export($default, true);

            default:
                return 'null';
        }
    }
}