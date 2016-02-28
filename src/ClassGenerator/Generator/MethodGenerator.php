<?php namespace DCarbone\PHPFHIR\ClassGenerator\Generator;

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

use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\SetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\ParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\PropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class MethodGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Generator
 */
abstract class MethodGenerator
{
    /**
     * @param ClassTemplate $classTemplate
     * @param PropertyTemplate $propertyTemplate
     */
    public static function implementMethodsForProperty(ClassTemplate $classTemplate, PropertyTemplate $propertyTemplate)
    {
        if ($propertyTemplate->requiresGetter())
            $classTemplate->addMethod(self::createGetter($propertyTemplate));

        if ($propertyTemplate->requireSetter())
            $classTemplate->addMethod(self::createSetter($propertyTemplate));
    }

    /**
     * @param PropertyTemplate $propertyTemplate
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate
     */
    public static function createGetter(PropertyTemplate $propertyTemplate)
    {
        $getterTemplate = new GetterMethodTemplate($propertyTemplate);
        $getterTemplate->addLineToBody(sprintf('return $this->%s;', $propertyTemplate->getName()));
        return $getterTemplate;
    }

    /**
     * @param PropertyTemplate $propertyTemplate
     * @return SetterMethodTemplate
     */
    public static function createSetter(PropertyTemplate $propertyTemplate)
    {
        $paramTemplate = new ParameterTemplate($propertyTemplate);

        if ($propertyTemplate->isCollection())
        {
            $methodBody = sprintf(
                '$this->%s[] = %s;',
                $propertyTemplate->getName(),
                NameUtils::getPropertyVariableName($paramTemplate->getProperty()->getName())
            );
        }
        else
        {
            $methodBody = sprintf(
                '$this->%s = %s;',
                $propertyTemplate->getName(),
                NameUtils::getPropertyVariableName($paramTemplate->getProperty()->getName())
            );
        }

        $setterTemplate = new SetterMethodTemplate($propertyTemplate);
        $setterTemplate->addParameter($paramTemplate);
        $setterTemplate->addLineToBody($methodBody);

        return $setterTemplate;
    }
}