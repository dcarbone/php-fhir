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
 * Class AbstractMethodTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
abstract class AbstractMethodTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;

    /** @var PHPScopeEnum */
    protected $scope;

    /** @var array */
    protected $body = array();

    /**
     * Constructor
     *
     * @param string $name
     * @param PHPScopeEnum $scope
     */
    public function __construct($name, PHPScopeEnum $scope)
    {
        if (NameUtils::isValidFunctionName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Function name "'.$name.'" is not valid.');

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
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $body
     */
    public function setBody(array $body)
    {
        $this->body = $body;
    }

    /**
     * @param string $line
     */
    public function addLineToBody($line)
    {
        $this->body[] = $line;
    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }
}