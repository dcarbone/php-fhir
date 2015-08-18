<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\ElementTypeEnum;
use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Enum\SimplePropertyTypesEnum;
use PHPFHIR\Template\ClassTemplate;
use PHPFHIR\Template\PropertyTemplate;
use PHPFHIR\XSDMap;

/**
 * Class PropertyGeneratorUtils
 * @package PHPFHIR\Utilities
 */
abstract class PropertyGeneratorUtils
{
    /**
     * @param SimplePropertyTypesEnum $type
     * @return string
     */
    public static function getSimpleTypeVariableType(SimplePropertyTypesEnum $type)
    {
        $strType = (string)$type;
        switch($strType)
        {
            case SimplePropertyTypesEnum::BOOLEAN:
            case SimplePropertyTypesEnum::INTEGER:
            case SimplePropertyTypesEnum::STRING:
                return $strType;

            case SimplePropertyTypesEnum::DECIMAL:
                return 'float';

            case SimplePropertyTypesEnum::UUID:
            case SimplePropertyTypesEnum::OID:
            case SimplePropertyTypesEnum::ID:
            case SimplePropertyTypesEnum::XML_ID_REF:
            case SimplePropertyTypesEnum::URI:
            case SimplePropertyTypesEnum::BASE_64_BINARY:
            case SimplePropertyTypesEnum::CODE:
                return 'string';

            case SimplePropertyTypesEnum::INSTANT:
            case SimplePropertyTypesEnum::DATE:
            case SimplePropertyTypesEnum::DATETIME:
                return '\\DateTime';

            default:
                throw new \RuntimeException('No variable type mapping exists for simple property "'.$strType.'"');
        }
    }

    /**
     * @param SimplePropertyTypesEnum $type
     * @return null|string
     */
    public static function getSimpleTypeVariableTypeHintingValue(SimplePropertyTypesEnum $type)
    {
        switch((string)$type)
        {
            case SimplePropertyTypesEnum::INSTANT:
            case SimplePropertyTypesEnum::DATE:
            case SimplePropertyTypesEnum::DATETIME:
                return '\\DateTime';

            default:
                return null;
        }
    }

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
                PropertyGeneratorUtils::implementAttributeProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::CHOICE:
                PropertyGeneratorUtils::implementChoiceProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::SEQUENCE:
                PropertyGeneratorUtils::implementSequenceProperty($XSDMap, $propertyElement, $classTemplate);
                break;
            case ElementTypeEnum::UNION:
                PropertyGeneratorUtils::implementUnionProperty($XSDMap, $propertyElement, $classTemplate);
                break;
        }
    }

    /**
     * @param XSDMap $XSDMap
     * @param string $name
     * @param string $type
     * @param string|null $maxOccurs
     * @param string|null|array $documentation
     * @param ClassTemplate $classTemplate
     * @return PropertyTemplate
     */
    public static function buildProperty(XSDMap $XSDMap, $name, $type, $maxOccurs, $documentation, ClassTemplate $classTemplate)
    {
        // TODO: Implement this better:
        $type = str_replace('-primitive', '', $type);

        if (preg_match('{^[A-Z]}S', $type))
            return self::buildComplexProperty($XSDMap, $name, $type, $maxOccurs, $documentation, $classTemplate);

        return self::buildSimpleProperty($name, $type, $maxOccurs, $documentation);
    }

    /**
     * @param string $name
     * @param string $type
     * @param string|null $maxOccurs
     * @param string|array|null $documentation
     * @return PropertyTemplate
     */
    public static function buildSimpleProperty($name, $type, $maxOccurs, $documentation)
    {
        $propertyTemplate = new PropertyTemplate();

        $propertyTemplate->setName($name);
        $propertyTemplate->addType(self::getSimpleTypeVariableType(new SimplePropertyTypesEnum(strtolower($type))));
        $propertyTemplate->setIsCollection('unbounded' === $maxOccurs || (is_numeric($maxOccurs) && (int)$maxOccurs > 1));
        $propertyTemplate->setDocumentation($documentation);

        return $propertyTemplate;
    }

    /**
     * @param XSDMap $XSDMap
     * @param string $name
     * @param string $type
     * @param string|null $maxOccurs
     * @param string|array|null $documentation
     * @param ClassTemplate $classTemplate
     * @return PropertyTemplate
     */
    public static function buildComplexProperty(XSDMap $XSDMap, $name, $type, $maxOccurs, $documentation, ClassTemplate $classTemplate)
    {
        $propertyTemplate = new PropertyTemplate();

        $classTemplate->addUse($XSDMap->getClassUseStatementForObject($type));
        $propertyTemplate->addType($XSDMap->getClassNameForObject($type));
        $propertyTemplate->setName($name);
        $propertyTemplate->setIsCollection('unbounded' === $maxOccurs || (is_numeric($maxOccurs) && (int)$maxOccurs > 1));
        $propertyTemplate->setDocumentation($documentation);

        return $propertyTemplate;
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
            $element = $elements[0];

            $attributes = $element->attributes();
            $name = (string)$attributes['name'];
            $type = (string)$attributes['type'];
            $maxOccurs = (string)$attributes['maxOccurs'];

            $propertyTemplate = self::buildProperty(
                $XSDMap,
                $name,
                $type,
                $maxOccurs,
                XMLUtils::getDocumentation($element),
                $classTemplate);

            $propertyTemplate->setScope(new PHPScopeEnum('private'));
            $propertyTemplate->setDocumentation(XMLUtils::getDocumentation($element));

            $classTemplate->addProperty($propertyTemplate);

            MethodGeneratorUtils::implementMethodsForProperty($classTemplate, $propertyTemplate);
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
                $propTemplate = self::buildProperty($XSDMap, $name, $type, $maxOccurs, $documentation, $classTemplate);
            }
            else if ('' === $name)
            {
                $type = NameUtils::getComplexTypeClassName($ref);
                $propTemplate = self::buildProperty($XSDMap, $type, $type, $maxOccurs, $documentation, $classTemplate);
            }
            else
            {
                trigger_error('Unable to parse choice property with definition '.(string)$_element);
                continue;
            }

            $propTemplate->setScope(new PHPScopeEnum('private'));
            $propTemplate->setDocumentation($documentation);

            $classTemplate->addProperty($propTemplate);

            MethodGeneratorUtils::implementMethodsForProperty($classTemplate, $propTemplate);
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

        $propertyTemplate = self::buildProperty($XSDMap, $name, $type, 1, XMLUtils::getDocumentation($attribute), $classTemplate);

        $classTemplate->addProperty($propertyTemplate);

        MethodGeneratorUtils::implementMethodsForProperty($classTemplate, $propertyTemplate);
    }

    public static function implementUnionProperty(XSDMap $XSDMap, \SimpleXMLElement $union, ClassTemplate $classTemplate)
    {

    }
}