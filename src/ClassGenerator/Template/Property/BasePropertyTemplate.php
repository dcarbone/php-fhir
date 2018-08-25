<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\Property;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class PropertyTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\Property
 */
class BasePropertyTemplate extends AbstractTemplate
{
    /** @var \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry|null */
    protected $mapEntry;

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
    protected $htmlValue = false;

    /** @var mixed */
    protected $defaultValue;

    /** @var bool */
    protected $requiresGetter;
    /** @var bool */
    protected $requireSetter;

    protected $choiceElementNames = [];

    /**
     * BasePropertyTemplate constructor.
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
     * @param \DCarbone\PHPFHIR\ClassGenerator\Enum\PHPScopeEnum|null $scope
     * @param bool $requiresGetter
     * @param bool $requiresSetter
     */
    public function __construct(XSDMapEntry $mapEntry = null,
                                PHPScopeEnum $scope = null,
                                $requiresGetter = true,
                                $requiresSetter = true)
    {
        $this->mapEntry = $mapEntry;
        if (null === $scope) {
            $this->scope = new PHPScopeEnum(PHPScopeEnum::_PUBLIC);
        } else {
            $this->scope = $scope;
        }
        $this->requiresGetter = $requiresGetter;
        $this->requireSetter = $requiresSetter;
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry|null
     */
    public function getXSDMapEntry()
    {
        return $this->mapEntry;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
     * @return $this
     */
    public function setXSDMapEntry(XSDMapEntry $mapEntry)
    {
        $this->mapEntry = $mapEntry;
        return $this;
    }

    /**
     * @return string
     */
    public function getFHIRElementType()
    {
        return $this->fhirElementType;
    }

    /**
     * @param $fhirElementType
     * @return $this
     */
    public function setFHIRElementType($fhirElementType)
    {
        $this->fhirElementType = $fhirElementType;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasHTMLValue()
    {
        return $this->htmlValue;
    }

    /**
     * Whether the value of this property is an HTML string or not
     *
     * @param bool $html
     * @return $this
     */
    public function setHTMLValue($html)
    {
        $this->htmlValue = $html;
        return $this;
    }

    /**
     * @return boolean
     */
    public function requiresGetter()
    {
        return $this->requiresGetter;
    }

    /**
     * @return boolean
     */
    public function requireSetter()
    {
        return $this->requireSetter;
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
            $this->getPHPType(),
            ($this->isCollection() ? '[]' : ''),
            (string)$this->getScope(),
            NameUtils::getPropertyVariableName($this->getName()),
            ($this->isCollection() ? '[]' : $this->determineDefaultValueOutput())
        );
    }

    /**
     * @return boolean
     */
    public function isPrimitive()
    {
        return $this->primitive;
    }

    /**
     * @param $primitive
     * @return $this
     */
    public function setPrimitive($primitive)
    {
        $this->primitive = $primitive;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isList()
    {
        return $this->list;
    }

    /**
     * @param $list
     * @return $this
     */
    public function setList($list)
    {
        $this->list = $list;
        return $this;
    }

    /**
     * @return string
     */
    public function getPHPType()
    {
        return $this->phpType;
    }

    /**
     * @param $phpType
     * @return $this
     */
    public function setPHPType($phpType)
    {
        $this->phpType = $phpType;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        return $this->collection;
    }

    /**
     * @param $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Enum\PHPScopeEnum $scope
     * @return $this
     */
    public function setScope(PHPScopeEnum $scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        if (NameUtils::isValidVariableName($name)) {
            $this->name = $name;
        } else {
            throw new \InvalidArgumentException(sprintf('Specified property name "%s" is not valid.', $name));
        }
        return $this;
    }

    /**
     * @return mixed|null|string
     */
    protected function determineDefaultValueOutput()
    {
        $default = $this->getDefaultValue();
        switch (gettype($default)) {
            case 'string':
                return sprintf('\'%s\'', $default);

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

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param $defaultValue
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }
}