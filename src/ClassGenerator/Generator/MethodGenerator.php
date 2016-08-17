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
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\BaseParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\PropertyParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class MethodGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Generator
 */
abstract class MethodGenerator
{
    private static $overrides = array();

    /**
     * @param array $overrides
     */
    public static function setOverrides(array $overrides)
    {
        self::$overrides = $overrides;
    }

    /**
     * @param ClassTemplate $classTemplate
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $propertyTemplate
     */
    public static function implementMethodsForProperty(ClassTemplate $classTemplate, BasePropertyTemplate $propertyTemplate)
    {
        if ($propertyTemplate->requiresGetter())
            $classTemplate->addMethod(self::createGetter($propertyTemplate));

        if ($propertyTemplate->requireSetter())
            $classTemplate->addMethod(self::createSetter($propertyTemplate));
    }

    /**
     * @param BasePropertyTemplate $propertyTemplate
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate
     */
    public static function createGetter(BasePropertyTemplate $propertyTemplate)
    {
        $getterTemplate = new GetterMethodTemplate($propertyTemplate);
        $getterTemplate->addLineToBody(sprintf('return $this->%s;', $propertyTemplate->getName()));
        return $getterTemplate;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $propertyTemplate
     * @return SetterMethodTemplate
     */
    public static function createSetter(BasePropertyTemplate $propertyTemplate)
    {
        $paramTemplate = new PropertyParameterTemplate($propertyTemplate);

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

                if ('_fhirElementName' === $name || 'value' === $name)
                    continue;

                $simple = false;
                break;
            }
        }
        else
        {
            $simple = false;
        }

        $elementName = $classTemplate->getElementName();

        if ($simple)
        {
            $method->setReturnValueType('string|int|float|bool|null');
            $method->setReturnStatement('$this->value');
        }
        // ResourceContainer and Resource.Inline need to just pass back the resource they contain.
        else if ('ResourceContainer' === $elementName || 'Resource.Inline' === $elementName)
        {
            $method->setReturnValueType('array');

            foreach($properties as $property)
            {
                $name = $property->getName();
                if ('_fhirElementName' === $name)
                    continue;

                $method->addLineToBody(sprintf(
                    'if (null !== $this->%s) return $this->%s->jsonSerialize();',
                    $name,
                    $name
                ));
            }

            // This is here just in case the ResourceContainer wasn't populated correctly for whatever reason.
            $method->setReturnStatement('array()');
        }
        else
        {
            $method->setReturnValueType('array');

            // Determine if this class is a child...
            if (null === $classTemplate->getExtendedElementMapEntry())
                $method->addLineToBody('$json = array();');
            else
                $method->addLineToBody('$json = parent::jsonSerialize();');

            // Unfortunately for the moment this value will potentially be overwritten several times during
            // JSOn generation...
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

    /**
     * @param ClassTemplate $classTemplate
     */
    public static function implementXMLSerialize(ClassTemplate $classTemplate)
    {
        $method = new BaseMethodTemplate('xmlSerialize');
        $method->addParameter(new BaseParameterTemplate('returnSXE', 'boolean', 'false'));
        $method->addParameter(new BaseParameterTemplate('sxe', '\\SimpleXMLElement', 'null'));
        $method->setReturnStatement('$sxe->saveXML()');
        $method->setReturnValueType('string|\\SimpleXMLElement');
        $classTemplate->addMethod($method);

        $properties = $classTemplate->getProperties();

        $simple = true;
        if (2 === count($properties))
        {
            foreach($properties as $property)
            {
                $name = $property->getName();

                if ('_fhirElementName' === $name || 'value' === $name)
                    continue;

                $simple = false;
                break;
            }
        }
        else
        {
            $simple = false;
        }

        $rootElementName = str_replace(NameUtils::$classNameSearch, NameUtils::$classNameReplace, $classTemplate->getElementName());
        // If this is the root object...
        $method->addLineToBody(sprintf(
            'if (null === $sxe) $sxe = new \\SimpleXMLElement(\'<%s xmlns="%s"></%s>\');',
            $rootElementName,
            FHIR_XMLNS,
            $rootElementName
        ));

        // For simple properties we need to simply add an attribute.
        if ($simple)
        {
            $method->addLineToBody('$sxe->addAttribute(\'value\', $this->value);');
        }
        else
        {
            // Determine if this class is a child...
            if ($classTemplate->getExtendedElementMapEntry())
                $method->addLineToBody('parent::xmlSerialize(true, $sxe);');

            foreach($properties as $property)
            {
                $name = $property->getName();

                if ('_fhirElementName' === $name)
                    continue;

                // Check if there are overrides for this element
                $propertyOverrides = array();
                if (array_key_exists($name, self::$overrides)) {
                    $propertyOverrides = self::$overrides[$name];
                }

                if ($property->isCollection())
                {
                    $method->addLineToBody(sprintf(
                        'if (0 < count($this->%s)) {',
                        $name
                    ));
                    $method->addLineToBody(sprintf(
                        '    foreach($this->%s as $%s) {',
                        $name,
                        $name
                    ));
                    $method->addLineToBody(sprintf(
                        '        $%s->xmlSerialize(true, $sxe->addChild(\'%s\'));',
                        $name,
                        $name
                    ));
                    $method->addLineToBody('    }');
                    $method->addLineToBody('}');
                }
                else if ($property->isPrimitive() || $property->isList() || $property->isHTML())
                {
                    $method->addLineToBody(sprintf(
                        'if (null !== $this->%s) {',
                        $name
                    ));

                    if (array_key_exists('attribute', $propertyOverrides) && $propertyOverrides['attribute'] === true) {
                        $attributeName = 'value';
                        if (array_key_exists('elementName', $propertyOverrides)) {
                            $attributeName = $propertyOverrides['elementName'];
                        }

                        $method->addLineToBody(sprintf(
                            '    $sxe->addAttribute(\'%s\', (string)$this->%s);',
                            $attributeName,
                            $name
                        ));
                    } else {
                        $elementName = $name;
                        if(array_key_exists('attribute', $propertyOverrides) && array_key_exists('elementName', $propertyOverrides) && $propertyOverrides['attribute'] === false){
                            $elementName = $propertyOverrides['elementName'];
                        }
                        $method->addLineToBody(sprintf(
                            '    $%sElement = $sxe->addChild(\'%s\');',
                            $name,
                            $elementName
                        ));
                        $method->addLineToBody(sprintf(
                            '    $%sElement->addAttribute(\'value\', (string)$this->%s);',
                            $name,
                            $name
                        ));
                    }
                    $method->addLineToBody('}');
                }
                else
                {
                    $method->addLineToBody(sprintf(
                        'if (null !== $this->%s) $this->%s->xmlSerialize(true, $sxe->addChild(\'%s\'));',
                        $name,
                        $name,
                        $name
                    ));
                }
            }
        }

        $method->addLineToBody('if ($returnSXE) return $sxe;');
    }
}
