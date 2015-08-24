<?php namespace PHPFHIR\Generator;

use PHPFHIR\Enum\ElementTypeEnum;
use PHPFHIR\Template\ClassTemplate;
use PHPFHIR\Utilities\XMLUtils;
use PHPFHIR\XSDMap;

/**
 * Class ClassGenerator
 * @package PHPFHIR\Utilities
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
        $classTemplate = new ClassTemplate();
        $classTemplate->setClassName($data['className']);
        $classTemplate->setNamespace($data['rootNS']);

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
     * @param \SimpleXMLElement $extension
     * @param ClassTemplate $classTemplate
     */
    public static function parseExtension(XSDMap $XSDMap, \SimpleXMLElement $extension, ClassTemplate $classTemplate)
    {
        self::determineExtendedClass($XSDMap, $extension, $classTemplate);
        foreach($extension->children('xs', true) as $_element)
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
            }
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sxe
     * @param ClassTemplate $classTemplate
     */
    public static function determineExtendedClass(XSDMap $XSDMap, \SimpleXMLElement $sxe, ClassTemplate $classTemplate)
    {
        // First, attempt to determine if this class extends a base class
        if ($baseObjectName = XMLUtils::getBaseObjectName($sxe))
        {
            $baseClassName = $XSDMap->getClassNameForObject($baseObjectName);
            $useStatement = $XSDMap->getClassUseStatementForObject($baseObjectName);
            $classTemplate->addUse($useStatement);
            $classTemplate->setExtends($baseClassName);
        }
        // Otherwise, attempt to find a "restriction" class to extend
        else if ($restrictionObjectName = XMLUtils::getRestrictionObjectName($sxe))
        {
            $baseClassName = $XSDMap->getClassNameForObject($restrictionObjectName);
            $useStatement = $XSDMap->getClassUseStatementForObject($restrictionObjectName);
            $classTemplate->addUse($useStatement);
            $classTemplate->setExtends($baseClassName);
        }
    }
}