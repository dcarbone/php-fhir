<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\Method;

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
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class SetterMethodTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class SetterMethodTemplate extends BaseMethodTemplate
{
    /**
     * Constructor
     *
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $propertyTemplate
     */
    public function __construct(BasePropertyTemplate $propertyTemplate)
    {
        if ($propertyTemplate->isCollection())
            $name = sprintf('add%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));
        else
            $name = sprintf('set%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));

        parent::__construct($name, new PHPScopeEnum(PHPScopeEnum::_PUBLIC));

        $this->setDocumentation($propertyTemplate->getDocumentation());
        $this->setReturnValueType('$this');
        $this->setReturnStatement('$this');
    }
}