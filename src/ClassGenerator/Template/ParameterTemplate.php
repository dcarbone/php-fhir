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

use PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class ParameterTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class ParameterTemplate
{
    /** @var string */
    protected $name;

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
        $this->name = $propertyTemplate->getName();
        $this->propertyTypes = $propertyTemplate->getTypes();
        $this->propertyIsCollection = $propertyTemplate->isCollection();
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
     * @param bool $forceSingle
     * @return string
     */
    public function getParamDocBlock($forceSingle = false)
    {
        if ($this->propertyIsCollection && !$forceSingle)
            return sprintf('@param %s[] %s', implode('[]|', $this->getPropertyTypes()), NameUtils::getPropertyVariableName($this->getName()));

        return sprintf('@param %s %s', implode('|', $this->getPropertyTypes()), NameUtils::getPropertyVariableName($this->getName()));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return NameUtils::getPropertyVariableName($this->getName());
    }
}