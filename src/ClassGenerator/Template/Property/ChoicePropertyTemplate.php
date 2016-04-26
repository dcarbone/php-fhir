<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\Property;

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

/**
 * Class ChoicePropertyTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\Property
 */
class ChoicePropertyTemplate extends BasePropertyTemplate
{
    private $_possibleValueElementNames = array();

    /**
     * @return array
     */
    public function getPossibleValueElementNames()
    {
        return $this->_possibleValueElementNames;
    }

    /**
     * @param string $name
     */
    public function addPossibleValueElementName($name)
    {
        $this->_possibleValueElementNames[] = $name;
        $this->_possibleValueElementNames = array_unique($this->_possibleValueElementNames);
    }
}