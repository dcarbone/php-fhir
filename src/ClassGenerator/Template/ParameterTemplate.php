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

use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class ParameterTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class ParameterTemplate extends AbstractTemplate
{
    /** @var string */
    private $_name;

    /** @var array[] */
    private $_propertyTypes;

    /** @var string[] */
    private $_propertyPHPTypes;

    /** @var bool */
    private $_propertyIsCollection;

    /**
     * Constructor
     *
     * @param PropertyTemplate $propertyTemplate
     */
    public function __construct(PropertyTemplate $propertyTemplate)
    {
        $this->_name = $propertyTemplate->getName();
        $this->_propertyTypes = $propertyTemplate->getTypes();
        $this->_propertyPHPTypes = $propertyTemplate->getPHPTypes();
        $this->_propertyIsCollection = $propertyTemplate->isCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return array[]
     */
    public function getPropertyTypes()
    {
        return $this->_propertyTypes;
    }

    /**
     * @return string[]
     */
    public function getPropertyPHPTypes()
    {
        return $this->_propertyPHPTypes;
    }

    /**
     * @return boolean
     */
    public function propertyIsCollection()
    {
        return $this->_propertyIsCollection;
    }

    /**
     * @param bool $forceSingle
     * @return string
     */
    public function getParamDocBlockFragment($forceSingle = false)
    {
        if ($this->_propertyIsCollection && !$forceSingle)
        {
            return sprintf(
                '@param %s[] %s',
                implode('[]|', array_values($this->getPropertyPHPTypes())),
                NameUtils::getPropertyVariableName($this->getName())
            );
        }

        return sprintf(
            '@param %s %s',
            implode('|', array_values($this->getPropertyPHPTypes())),
            NameUtils::getPropertyVariableName($this->getName())
        );
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return NameUtils::getPropertyVariableName($this->getName());
    }
}