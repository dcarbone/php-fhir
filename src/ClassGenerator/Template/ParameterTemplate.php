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
    /** @var PropertyTemplate */
    private $_property;

    /**
     * Constructor
     *
     * @param PropertyTemplate $propertyTemplate
     */
    public function __construct(PropertyTemplate $propertyTemplate)
    {
        $this->_property = $propertyTemplate;
    }

    /**
     * @return PropertyTemplate
     */
    public function getProperty()
    {
        return $this->_property;
    }

    /**
     * @param bool $forceSingle
     * @return string
     */
    public function getParamDocBlockFragment($forceSingle = false)
    {
        if ($this->getProperty()->isCollection() && !$forceSingle)
        {
            return sprintf(
                '@param %s[] %s',
                $this->getProperty()->getPhpType(),
                NameUtils::getPropertyVariableName($this->getProperty()->getName())
            );
        }

        return sprintf(
            '@param %s %s',
            $this->getProperty()->getPhpType(),
            NameUtils::getPropertyVariableName($this->getProperty()->getName())
        );
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return NameUtils::getPropertyVariableName($this->getProperty()->getName());
    }
}