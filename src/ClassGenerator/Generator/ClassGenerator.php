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
use PHPFHIR\ClassGenerator\Template\ClassTemplate;
use PHPFHIR\ClassGenerator\Utilities\PrimitiveTypeUtils;
use PHPFHIR\ClassGenerator\Utilities\XMLUtils;
use PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class ClassGenerator
 * @package PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassGenerator
{
    /**
     * @param XSDMap $XSDMap
     * @param array $data
     * @return ClassTemplate
     */
    public static function buildClassTemplate(XSDMap $XSDMap, array $data)
    {
        $classTemplate = new ClassTemplate($data['className'], $data['rootNS'], $data['pseudonym']);

        foreach($data['sxe']->children('xs', true) as $_element)
        {
            /** @var \SimpleXMLElement $_element */
            switch(strtolower($_element->getName()))
            {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                    PropertyGenerator::implementProperty($XSDMap, $_element, $classTemplate);
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

        return $classTemplate;
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
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                case ElementTypeEnum::ENUMERATION:
                    PropertyGenerator::implementProperty($XSDMap, $_element, $classTemplate);
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
                    PropertyGenerator::implementProperty($XSDMap, $_element, $classTemplate);
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
        // First, attempt to determine if this class extends a base class
        if ($baseObjectName = XMLUtils::getBaseObjectName($sxe))
        {
            $baseClassName = $XSDMap->getClassNameForObject($baseObjectName);
            $classTemplate->setExtends($baseClassName);

            $useStatement = $XSDMap->getClassUseStatementForObject($baseObjectName);
            if ($useStatement)
                $classTemplate->addUse($useStatement);
        }
        // Otherwise, attempt to find a "restriction" class to extend
        else if ($restrictionObjectName = XMLUtils::getObjectRestrictionBaseName($sxe))
        {
            if (0 === strpos($restrictionObjectName, 'xs:'))
            {
                $xmlPrimitive = PrimitiveTypeUtils::getXMLPrimitiveTypeClass(substr($restrictionObjectName, 3));
                $useStatement = get_class($xmlPrimitive);
                $baseClassName = basename($useStatement);
            }
            else
            {
                $baseClassName = $XSDMap->getClassNameForObject($restrictionObjectName);
                $useStatement = $XSDMap->getClassUseStatementForObject($restrictionObjectName);
            }

            $classTemplate->setExtends($baseClassName);

            if ($useStatement)
                $classTemplate->addUse($useStatement);
        }
    }
}