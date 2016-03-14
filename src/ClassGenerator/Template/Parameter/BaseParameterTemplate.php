<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\Parameter;

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

use DCarbone\PHPFHIR\ClassGenerator\Template\AbstractTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class BaseParameterTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\Parameter
 */
class BaseParameterTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;
    /** @var string */
    protected $phpType;
    /** @var null|string */
    protected $defaultValue;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $phpType
     * @param null|string $defaultValue
     */
    public function __construct($name, $phpType = 'mixed', $defaultValue = null)
    {
        if (NameUtils::isValidVariableName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException(sprintf('Specified parameter name "%s" is not valid.', $name));

        $this->phpType = $phpType;
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
     * @return null|string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param null|string $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string
     */
    public function getParamDocBlockFragment()
    {
        return sprintf(
            '@param %s %s',
            $this->getPhpType(),
            NameUtils::getPropertyVariableName($this->getName())
        );
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        if (is_string($this->defaultValue))
        {
            return sprintf(
                '%s = %s',
                NameUtils::getPropertyVariableName($this->getName()),
                $this->getDefaultValue()
            );
        }

        return NameUtils::getPropertyVariableName($this->getName());
    }
}