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

use DCarbone\PHPFHIR\ClassGenerator\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\ClassGenerator\Enum\PHPScopeEnum;
use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\ClassTypeUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\XMLUtils;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class ClassGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassGenerator
{
    /** @var string */
    private static $_outputNamespace;

    /**
     * @param string $outputNamespace
     */
    public static function init($outputNamespace)
    {
        self::$_outputNamespace = $outputNamespace;
    }

    /**
     * @param XSDMap $XSDMap
     * @param XSDMap\XSDMapEntry $mapEntry
     * @return ClassTemplate
     */
    public static function buildFHIRElementClassTemplate(XSDMap $XSDMap, XSDMap\XSDMapEntry $mapEntry)
    {
        $classTemplate = new ClassTemplate(
            $mapEntry->fhirElementName,
            $mapEntry->className,
            $mapEntry->namespace,
            ClassTypeUtils::getComplexClassType($mapEntry->sxe)
        );

        foreach($mapEntry->sxe->children('xs', true) as $_element)
        {
            /** @var \SimpleXMLElement $_element */
            switch(strtolower($_element->getName()))
            {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                    PropertyGenerator::implementProperty($XSDMap, $classTemplate, $_element);
                    break;

                case ElementTypeEnum::ANNOTATION:
                    $classTemplate->setDocumentation(XMLUtils::getDocumentation($_element));
                    break;

                case ElementTypeEnum::COMPLEX_TYPE:
                    self::parseComplexType($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::COMPLEX_CONTENT:
                    self::parseComplexContent($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    self::parseSimpleType($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_CONTENT:
                    self::parseSimpleContent($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    self::parseRestriction($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::EXTENSION:
                    self::parseExtension($XSDMap, $_element, $classTemplate);
                    break;
            }
        }

        self::addBaseClassProperties($classTemplate, $mapEntry);

        foreach($classTemplate->getProperties() as $propertyTemplate)
        {
            MethodGenerator::implementMethodsForProperty($classTemplate, $propertyTemplate);
        }

        self::addBaseClassInterfaces($classTemplate);
        self::addBaseClassMethods($classTemplate);

        return $classTemplate;
    }

    /**
     * @param ClassTemplate $classTemplate
     * @param XSDMap\XSDMapEntry $mapEntry
     */
    public static function addBaseClassProperties(ClassTemplate $classTemplate, XSDMap\XSDMapEntry $mapEntry)
    {
        // Add the source element name to each class...
        $property =  new BasePropertyTemplate(new PHPScopeEnum(PHPScopeEnum::_PRIVATE), true, false);
        $property->setDefaultValue($mapEntry->fhirElementName);
        $property->setName('_fhirElementName');
        $property->setPHPType('string');
        $property->setPrimitive(true);
        $classTemplate->addProperty($property);
    }

    /**
     * @param ClassTemplate $classTemplate
     */
    public static function addBaseClassMethods(ClassTemplate $classTemplate)
    {
        MethodGenerator::implementToString($classTemplate);
        MethodGenerator::implementJsonSerialize($classTemplate);
        MethodGenerator::implementXMLSerialize($classTemplate);
    }

    /**
     * @param ClassTemplate $classTemplate
     */
    public static function addBaseClassInterfaces(ClassTemplate $classTemplate)
    {
        $classTemplate->addImplementedInterface(sprintf('%s\\JsonSerializable', self::$_outputNamespace));
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $complexContent
     * @param ClassTemplate $classTemplate
     */
    public static function parseComplexContent(XSDMap $XSDMap, \SimpleXMLElement $complexContent, ClassTemplate $classTemplate)
    {
        self::_parseContent($XSDMap, $complexContent, $classTemplate);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $complexType
     * @param ClassTemplate $classTemplate
     */
    public static function parseComplexType(XSDMap $XSDMap, \SimpleXMLElement $complexType, ClassTemplate $classTemplate)
    {
        self::_parseContent($XSDMap, $complexType, $classTemplate);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $simpleContent
     * @param ClassTemplate $classTemplate
     */
    public static function parseSimpleContent(XSDMap $XSDMap, \SimpleXMLElement $simpleContent, ClassTemplate $classTemplate)
    {
        self::_parseContent($XSDMap, $simpleContent, $classTemplate);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $simpleType
     * @param ClassTemplate $classTemplate
     */
    public static function parseSimpleType(XSDMap $XSDMap, \SimpleXMLElement $simpleType, ClassTemplate $classTemplate)
    {
        self::_parseContent($XSDMap, $simpleType, $classTemplate);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $restriction
     * @param ClassTemplate $classTemplate
     */
    public static function parseRestriction(XSDMap $XSDMap, \SimpleXMLElement $restriction, ClassTemplate $classTemplate)
    {
        self::determineParentClass($XSDMap, $restriction, $classTemplate);
        self::_implementProperties($XSDMap, $restriction, $classTemplate);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $extension
     * @param ClassTemplate $classTemplate
     */
    public static function parseExtension(XSDMap $XSDMap, \SimpleXMLElement $extension, ClassTemplate $classTemplate)
    {
        self::determineParentClass($XSDMap, $extension, $classTemplate);
        self::_implementProperties($XSDMap, $extension, $classTemplate);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $choice
     * @param ClassTemplate $classTemplate
     */
    public static function parseChoice(XSDMap $XSDMap,
                                       \SimpleXMLElement $choice,
                                       ClassTemplate $classTemplate)
    {
        PropertyGenerator::implementProperty($XSDMap, $classTemplate, $choice);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sxe
     * @param ClassTemplate $classTemplate
     */
    public static function determineParentClass(XSDMap $XSDMap, \SimpleXMLElement $sxe, ClassTemplate $classTemplate)
    {
        $fhirElementName = XMLUtils::getBaseFHIRElementNameFromExtension($sxe);
        if (null === $fhirElementName)
            $fhirElementName = XMLUtils::getBaseFHIRElementNameFromRestriction($sxe);

        if (null === $fhirElementName)
            return;

        if (0 === strpos($fhirElementName, 'xs'))
            $fhirElementName = substr($fhirElementName, 3);

        self::findParentElementXSDMapEntry($fhirElementName, $XSDMap, $classTemplate);
    }

    /**
     * @param string $fhirElementName
     * @param XSDMap $XSDMap
     * @param ClassTemplate $classTemplate
     */
    public static function findParentElementXSDMapEntry($fhirElementName, XSDMap $XSDMap, ClassTemplate $classTemplate)
    {
        if (isset($XSDMap[$fhirElementName]))
            $classTemplate->setExtendedElementMapEntry($XSDMap[$fhirElementName]);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $contentElement
     * @param ClassTemplate $classTemplate
     */
    private static function _parseContent(XSDMap $XSDMap, \SimpleXMLElement $contentElement, ClassTemplate $classTemplate)
    {
        foreach($contentElement->children('xs', true) as $_element)
        {
            /** @var \SimpleXMLElement $_element */
            switch(strtolower($_element->getName()))
            {

                case ElementTypeEnum::COMPLEX_CONTENT:
                    self::parseComplexContent($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::COMPLEX_TYPE:
                    self::parseComplexType($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_CONTENT:
                    self::parseSimpleContent($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    self::parseSimpleType($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::EXTENSION:
                    self::parseExtension($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    self::parseRestriction($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::CHOICE:
                    self::parseChoice($XSDMap, $_element, $classTemplate);
                    break;
            }
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sxe
     * @param ClassTemplate $classTemplate
     */
    private static function _implementProperties(XSDMap $XSDMap, \SimpleXMLElement $sxe, ClassTemplate $classTemplate)
    {
        foreach($sxe->children('xs', true) as $_element)
        {
            /** @var \SimpleXMLElement $_element */
            switch(strtolower($_element->getName()))
            {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::UNION:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::ENUMERATION:
                    PropertyGenerator::implementProperty($XSDMap, $classTemplate, $_element);
                    break;
            }
        }
    }
}