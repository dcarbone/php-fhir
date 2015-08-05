<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\PropertySourceTypeEnum;
use PHPFHIR\Template\ClassTemplate;
use PHPFHIR\XSDMap;

/**
 * Class ClassGeneratorUtils
 * @package PHPFHIR\Utilities
 */
abstract class ClassGeneratorUtils
{
    /**
     * @param array $data
     * @return ClassTemplate
     */
    public static function buildClassTemplate(array $data)
    {
        return new ClassTemplate(
            $data['rootNS'],
            $data['className']
        );
    }

    /**
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sxe
     * @param ClassTemplate $classTemplate
     */
    public static function determineRootProperties(XSDMap $XSDMap, \SimpleXMLElement $sxe, ClassTemplate $classTemplate)
    {
        foreach($sxe->xpath('xs:sequence') as $sequenceProperty)
        {
            PropertyGeneratorUtilities::implementSequenceProperty($XSDMap, $sequenceProperty, $classTemplate);
        }

        foreach($sxe->xpath('xs:choice') as $choiceProperty)
        {

        }

        foreach($sxe->xpath('xs:attribute') as $attributeProperty)
        {

        }

        foreach($sxe->xpath('xs:union') as $unionProperty)
        {

        }
    }

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