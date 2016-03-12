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

use DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum;
use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
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

    /**
     * @param ClassTemplate $classTemplate
     */
    public static function implementToString(ClassTemplate $classTemplate)
    {
        // Add __toString() method...
        $method = new BaseMethodTemplate('__toString');
        $classTemplate->addMethod($method);
        $method->setReturnValueType('string');

        if ($classTemplate->hasProperty('value'))
            $method->setReturnStatement('(string)$this->getValue()');
        else if ($classTemplate->hasProperty('id'))
            $method->setReturnStatement('(string)$this->getId()');
        else
            $method->setReturnStatement('$this->get_fhirElementName()');
    }

    /**
     * @param ClassTemplate $classTemplate
     */
    public static function implementJsonSerialize(ClassTemplate $classTemplate)
    {
        $method = new BaseMethodTemplate('jsonSerialize');
        $classTemplate->addMethod($method);

        $properties = $classTemplate->getProperties();

        $simple = true;
        if (2 === count($properties))
        {
            foreach($properties as $property)
            {
                $name = $property->getName();
                switch($name)
                {
                    case '_fhirElementName':
                    case 'value':
                        continue 2;

                    default:
                        $simple = false;
                        break 2;
                }
            }
        }
        else
        {
            $simple = false;
        }

        if ($simple)
        {
            $method->setReturnValueType('string|int|float|bool|null');
            $method->setReturnStatement('$this->value');
        }
        else
        {
            $method->setReturnValueType('array');
            $method->addLineToBody('$json = array();');

            switch((string)$classTemplate->getClassType())
            {
                case ComplexClassTypesEnum::RESOURCE:
                case ComplexClassTypesEnum::DOMAIN_RESOURCE:
                    $method->addLineToBody('$json[\'resourceType\'] = $this->_fhirElementName;');
                    break;
            }

            foreach($properties as $property)
            {
                $name = $property->getName();

                if ('_fhirElementName' === $name)
                    continue;

                if ($property->isCollection())
                {
                    $method->addLineToBody(sprintf(
                        'if (0 < count($this->%s)) {',
                        $name
                    ));
                    $method->addLineToBody(sprintf(
                        '    $json[\'%s\'] = array();',
                        $name
                    ));
                    $method->addLineToBody(sprintf(
                        '    foreach($this->%s as $%s) {',
                        $name,
                        $name
                    ));
                    $method->addLineToBody(sprintf(
                        '        $json[\'%s\'][] = $%s->jsonSerialize();',
                        $name,
                        $name
                    ));
                    $method->addLineToBody('    }');
                    $method->addLineToBody('}');
                }
                else if ($property->isPrimitive() || $property->isList() || $property->isHTML())
                {
                    $method->addLineToBody(sprintf(
                        'if (null !== $this->%s) $json[\'%s\'] = $this->%s;',
                        $name,
                        $name,
                        $name
                    ));
                }
                else
                {
                    $method->addLineToBody(sprintf(
                        'if (null !== $this->%s) $json[\'%s\'] = $this->%s->jsonSerialize();',
                        $name,
                        $name,
                        $name
                    ));
                }
            }

            $method->setReturnStatement('$json');
        }
    }
}