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
use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\PropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\XMLUtils;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class ClassGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassGenerator
{
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
            $mapEntry->namespace
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

                case ElementTypeEnum::COMPLEX_CONTENT:
                    self::parseComplexContent($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    self::parseRestriction($XSDMap, $_element, $classTemplate);
                    break;
            }
        }

        self::addBaseClassProperties($classTemplate, $mapEntry);

        $classIsChild = $classTemplate->getExtendedElementMapEntry() === null;

        foreach($classTemplate->getProperties() as $propertyTemplate)
        {
            if ('_fhirElementName' === $propertyTemplate->getName() && $classIsChild)
                continue;

            MethodGenerator::implementMethodsForProperty($classTemplate, $propertyTemplate);
        }

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
        $fhirElementName =  new PropertyTemplate(null, true, false);
        $fhirElementName->setDefaultValue($mapEntry->fhirElementName);
        $fhirElementName->setName('_fhirElementName');
        $fhirElementName->setPhpType('string');
        $fhirElementName->setPrimitive(true);
        $classTemplate->addProperty($fhirElementName);
    }

    /**
     * @param ClassTemplate $classTemplate
     */
    public static function addBaseClassMethods(ClassTemplate $classTemplate)
    {
        // Add __toString() method...
        $method = new BaseMethodTemplate('__toString');
        $method->setReturnValueType('string');

        if ($classTemplate->hasProperty('value'))
            $method->setReturnStatement('$this->getValue()');
        else if ($classTemplate->hasProperty('id'))
            $method->setReturnStatement('$this->getId()');
        else
            $method->setReturnStatement('$this->get_fhirElementName()');

        $classTemplate->addMethod($method);
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $complexContent
     * @param ClassTemplate $classTemplate
     */
    public static function parseComplexContent(XSDMap $XSDMap, \SimpleXMLElement $complexContent, ClassTemplate $classTemplate)
    {
        foreach($complexContent->children('xs', true) as $_element)
        {
            /** @var \SimpleXMLElement $_element */
            switch(strtolower($_element->getName()))
            {
                case ElementTypeEnum::EXTENSION:
                    self::parseExtension($XSDMap, $_element, $classTemplate);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    self::parseRestriction($XSDMap, $_element, $classTemplate);
                    break;
            }
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $restriction
     * @param ClassTemplate $classTemplate
     */
    public static function parseRestriction(XSDMap $XSDMap, \SimpleXMLElement $restriction, ClassTemplate $classTemplate)
    {
        self::determineParentClass($XSDMap, $restriction, $classTemplate);
        foreach($restriction->children('xs', true) as $_element)
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

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $extension
     * @param ClassTemplate $classTemplate
     */
    public static function parseExtension(XSDMap $XSDMap, \SimpleXMLElement $extension, ClassTemplate $classTemplate)
    {
        self::determineParentClass($XSDMap, $extension, $classTemplate);
        foreach($extension->children('xs', true) as $_element)
        {
            /** @var \SimpleXMLElement $_element */
            switch(strtolower($_element->getName()))
            {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                case ElementTypeEnum::ENUMERATION:
                    PropertyGenerator::implementProperty($XSDMap, $classTemplate, $_element);
                    break;
            }
        }
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

        if (0 !== strpos($fhirElementName, 'xs'))
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
}