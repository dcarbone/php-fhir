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
 * Class PropertyTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class PropertyTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;

    /** @var PHPScopeEnum */
    protected $scope;

    /** @var bool */
    protected $collection = false;

    /**
     * Will either be PHP class or scalar type string equivalent to end result of "gettype()"
     * @var string
     */
    protected $phpType;

    /** @var string */
    protected $fhirElementType;

    /** @var bool */
    protected $primitive = false;

    /** @var bool */
    protected $list = false;

    /** @var bool */
    protected $html = false;

    /** @var mixed */
    protected $defaultValue;

    /**
     * Constructor
     * @param PHPScopeEnum $scope
     */
    public function __construct(PHPScopeEnum $scope = null)
    {
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
     * @param string $name
     */
    public function setName($name)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException(sprintf('Specified property name "%s" is not valid.', $name));

        $this->name = $name;
    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param PHPScopeEnum $scope
     */
    public function setScope(PHPScopeEnum $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        return $this->collection;
    }

    /**
     * @param boolean $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return string
     */
    public function getPhpType()
    {
        return $this->phpType;
    }

    /**
     * @param string $phpType
     */
    public function setPhpType($phpType)
    {
        $this->phpType = $phpType;
    }

    /**
     * @return string
     */
    public function getFHIRElementType()
    {
        return $this->fhirElementType;
    }

    /**
     * @param string $fhirElementType
     */
    public function setFHIRElementType($fhirElementType)
    {
        $this->fhirElementType = $fhirElementType;
    }

    /**
     * @return boolean
     */
    public function isPrimitive()
    {
        return $this->primitive;
    }

    /**
     * @param boolean $primitive
     */
    public function setPrimitive($primitive)
    {
        $this->primitive = $primitive;
    }

    /**
     * @return boolean
     */
    public function isList()
    {
        return $this->list;
    }

    /**
     * @param boolean $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }

    /**
     * @return boolean
     */
    public function isHtml()
    {
        return $this->html;
    }

    /**
     * @param boolean $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return sprintf(
            "    /**\n%s     * @var %s%s%s\n     */\n    %s %s = %s;\n\n",
            $this->getDocBlockDocumentationFragment(),
            $this->isPrimitive() || $this->isList() ? '' : '\\',
            $this->getPhpType(),
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