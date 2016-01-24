<?php namespace PHPFHIR\ClassGenerator\Generator;

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

use PHPFHIR\ClassGenerator\Enum\ElementTypeEnum;
use PHPFHIR\ClassGenerator\Enum\PHPScopeEnum;
use PHPFHIR\ClassGenerator\Template\ClassTemplate;
use PHPFHIR\ClassGenerator\Template\PropertyTemplate;
use PHPFHIR\ClassGenerator\Utilities\NameUtils;
use PHPFHIR\ClassGenerator\Utilities\XMLUtils;
use PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class PropertyGenerator
 * @package PHPFHIR\ClassGenerator\Utilities
 */
abstract class PropertyGenerator
{
    /**
     * TODO: I don't like how this is utilized, really.  Should think of a better way to do it.
     *
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $propertyElement
     * @param ClassTemplate $classTemplate
     */
    public static function implementProperty(XSDMap $XSDMap, \SimpleXMLElement $propertyElement, ClassTemplate $classTemplate)
    {
        switch(strtolower($propertyElement->getName()))
        {
            case ElementTypeEnum::ATTRIBUTE:
                self::implementAttributeProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::CHOICE:
                self::implementChoiceProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::SEQUENCE:
                self::implementSequenceProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::UNION:
                self::implementUnionProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::ENUMERATION:
                self::implementEnumerationProperty($XSDMap, $propertyElement, $classTemplate);
                break;
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param ClassTemplate $classTemplate
     * @param string $name
     * @param string $type
     * @param string|null $maxOccurs
     * @param string|null|array $documentation
     * @param mixed $defaultValue
     * @return PropertyTemplate
     */
    public static function buildProperty(XSDMap $XSDMap,
                                         ClassTemplate $classTemplate,
                                         $name,
                                         $type,
                                         $maxOccurs,
                                         $documentation,
                                         $defaultValue = null)
    {
        if (preg_match('{^[A-Z]}S', $type))
            return self::buildComplexProperty($XSDMap, $classTemplate, $name, $type, $maxOccurs, $documentation, $defaultValue);

        return self::buildSimpleProperty($name, $type, $maxOccurs, $documentation, $defaultValue);
    }

    /**
     * @param string $name
     * @param string $type
     * @param string|null $maxOccurs
     * @param string|array|null $documentation
     * @param $defaultValue
     * @return PropertyTemplate
     */
    public static function buildSimpleProperty($name, $type, $maxOccurs, $documentation, $defaultValue)
    {
        $propertyTemplate = self::newPropertyTemplate($name, $maxOccurs, $documentation, $defaultValue);
        $propertyTemplate->addType($name, $type, $type);

        return $propertyTemplate;
    }

    /**
     * @param XSDMap $XSDMap
     * @param ClassTemplate $classTemplate
     * @param string $name
     * @param string $type
     * @param string|null $maxOccurs
     * @param string|array|null $documentation
     * @param $defaultValue
     * @return PropertyTemplate
     */
    public static function buildComplexProperty(XSDMap $XSDMap,
                                                ClassTemplate $classTemplate,
                                                $name,
                                                $type,
                                                $maxOccurs,
                                                $documentation,
                                                $defaultValue)
    {
        $propertyTemplate = self::newPropertyTemplate($name, $maxOccurs, $documentation, $defaultValue);
        $propertyTemplate->addType($type, $XSDMap->getClassNameForObject($type), $type);

        $useStatement = $XSDMap->getClassUseStatementForObject($type);
        if ($useStatement)
            $classTemplate->addUse($useStatement);

        return $propertyTemplate;
    }

    /**
     * @param string $name
     * @param string|number $maxOccurs
     * @param array|string|null $documentation
     * @param $defaultValue
     * @return PropertyTemplate
     */
    public static function newPropertyTemplate($name, $maxOccurs, $documentation, $defaultValue)
    {
        $propertyTemplate = new PropertyTemplate(
            $name,
            new PHPScopeEnum(PHPScopeEnum::_PUBLIC),
            self::determineIfCollection($maxOccurs),
            $defaultValue
        );

        $propertyTemplate->setDocumentation($documentation);

        return $propertyTemplate;
    }

    /**
     * @param string|number $maxOccurs
     * @return bool
     */
    public static function determineIfCollection($maxOccurs)
    {
        return 'unbounded' === strtolower($maxOccurs) || (is_numeric($maxOccurs) && (int)$maxOccurs > 1);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sequence
     * @param ClassTemplate $classTemplate
     */
    public static function implementSequenceProperty(XSDMap $XSDMap, \SimpleXMLElement $sequence, ClassTemplate $classTemplate)
    {
        // Check if this is a simple or complex sequence
        $elements = $sequence->xpath('xs:element');
        if (0 === count($elements))
        {
            foreach($sequence->children('xs', true) as $_element)
            {
                /** @var \SimpleXMLElement $_element */
                switch(strtolower($_element->getName()))
                {
                    case ElementTypeEnum::CHOICE:
                        self::implementChoiceProperty($XSDMap, $_element, $classTemplate);
                        break;
                }
            }
        }
        else
        {
            foreach($elements as $element)
            {
                $attributes = $element->attributes();
                $name = (string)$attributes['name'];
                $type = (string)$attributes['type'];

                // At the moment it appears this happens when a property
                // is supposed to contain HTML.  We'll see how long this holds true...
                if ('' === $name)
                {
                    if (isset($attributes['ref']))
                    {
                        $ref = (string)$attributes['ref'];

                        if (0 === strpos($ref, 'xhtml'))
                        {
                            $name = substr($ref, 6);
                            $type = 'string';
                        }
                        else
                        {
                            trigger_error(sprintf(
                                'Encountered property with no name attribute and with ref value "%s" on FHIR object "%s". Will not create property for it.',
                                $ref,
                                $classTemplate->getElementName()
                            ));

                            continue;
                        }
                    }
                    else
                    {
                        trigger_error(sprintf(
                            'Encountered property element with no "name" or "ref" attribute on FHIR object "%s".  Will not create property for it.',
                            $classTemplate->getElementName()
                        ));

                        continue;
                    }
                }

                $maxOccurs = (string)$attributes['maxOccurs'];

                $propertyTemplate = self::buildProperty(
                    $XSDMap,
                    $classTemplate,
                    $name,
                    $type,
                    $maxOccurs,
                    XMLUtils::getDocumentation($element)
                );

                $classTemplate->addProperty($propertyTemplate);

                MethodGenerator::implementMethodsForProperty($classTemplate, $propertyTemplate);
            }
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $choice
     * @param ClassTemplate $classTemplate
     */
    public static function implementChoiceProperty(XSDMap $XSDMap, \SimpleXMLElement $choice, ClassTemplate $classTemplate)
    {
        $attributes = $choice->attributes();
//        $minOccurs = (int)$attributes['minOccurs'];
        $maxOccurs = $attributes['maxOccurs'];
        $documentation = XMLUtils::getDocumentation($choice);

        foreach($choice->xpath('xs:element') as $_element)
        {
            $attributes = $_element->attributes();
            $name = (string)$attributes['name'];
            $ref = (string)$attributes['ref'];
            $type = (string)$attributes['type'];

            if ('' === $name && '' === $ref)
                throw new \RuntimeException('Unable to determine name of choice property in class '.$classTemplate->getClassName().'.');

            if ('' === $ref)
            {
                $propTemplate = self::buildProperty($XSDMap, $classTemplate, $name, $type, $maxOccurs, $documentation, null);
            }
            else if ('' === $name)
            {
                $type = NameUtils::getComplexTypeClassName($ref);
                $propTemplate = self::buildProperty($XSDMap, $classTemplate, $type, $type, $maxOccurs, $documentation, null);
            }
            else
            {
                trigger_error('Unable to parse choice property with definition '.(string)$_element);
                continue;
            }

            $classTemplate->addProperty($propTemplate);

            MethodGenerator::implementMethodsForProperty($classTemplate, $propTemplate);
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $attribute
     * @param ClassTemplate $classTemplate
     */
    public static function implementAttributeProperty(XSDMap $XSDMap, \SimpleXMLElement $attribute, ClassTemplate $classTemplate)
    {
        $attributes = $attribute->attributes();
        $name = (string)$attributes['name'];
        $type = (string)$attributes['type'];

        $propertyTemplate = self::buildProperty($XSDMap, $classTemplate, $name, $type, 1, XMLUtils::getDocumentation($attribute), null);

        $classTemplate->addProperty($propertyTemplate);

        MethodGenerator::implementMethodsForProperty($classTemplate, $propertyTemplate);
    }

    public static function implementUnionProperty(XSDMap $XSDMap, \SimpleXMLElement $union, ClassTemplate $classTemplate)
    {
        // TODO: Implement these!
    }

    public static function implementEnumerationProperty(XSDMap $XSDMap, \SimpleXMLElement $enumeration, ClassTemplate $classTemplate)
    {
        // TODO: Implement these!
    }
}