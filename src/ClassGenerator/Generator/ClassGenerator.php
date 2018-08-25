<?php namespace DCarbone\PHPFHIR\ClassGenerator\Generator;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\ClassTypeUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\XMLUtils;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap;
use DCarbone\PHPFHIR\Config;

/**
 * Class ClassGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassGenerator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap $XSDMap
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate
     */
    public static function buildFHIRElementClassTemplate(Config $config, XSDMap $XSDMap, XSDMap\XSDMapEntry $mapEntry)
    {
        $classTemplate = new ClassTemplate(
            $mapEntry->fhirElementName,
            $mapEntry->className,
            $mapEntry->namespace,
            $mapEntry,
            ClassTypeUtils::getComplexClassType($mapEntry->sxe)
        );

        foreach ($mapEntry->sxe->children('xs', true) as $element) {
            /** @var \SimpleXMLElement $element */
            switch (strtolower($element->getName())) {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                    PropertyGenerator::implementProperty($XSDMap, $classTemplate, $element);
                    break;

                case ElementTypeEnum::ANNOTATION:
                    $classTemplate->setDocumentation(XMLUtils::getDocumentation($element));
                    break;

                case ElementTypeEnum::COMPLEX_TYPE:
                    self::parseComplexType($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::COMPLEX_CONTENT:
                    self::parseComplexContent($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    self::parseSimpleType($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_CONTENT:
                    self::parseSimpleContent($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    self::parseRestriction($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::EXTENSION:
                    self::parseExtension($config, $XSDMap, $element, $classTemplate);
                    break;

                default:
                    $config->getLogger()->warning(sprintf(
                        'Unexpected element "%s" found: %s',
                        $element->getName(),
                        $element
                    ));
            }
        }

        self::addBaseClassProperties($classTemplate, $mapEntry);

        foreach ($classTemplate->getProperties() as $propertyTemplate) {
            MethodGenerator::implementMethodsForProperty($config, $classTemplate, $propertyTemplate);
        }

        self::addBaseClassInterfaces($classTemplate);
        self::addBaseClassMethods($config, $classTemplate);

        // TODO: Find better place for this...
        if ('ResourceContainer' === $classTemplate->getXSDMapEntry()->getFHIRElementName()) {
            $method = new BaseMethodTemplate($config, 'getResource');
            $method->setReturnValueType('mixed');
            $method->addLineToBody('return $this->jsonSerialize();');
            $classTemplate->addMethod($method);
        }

        return $classTemplate;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap $XSDMap
     * @param \SimpleXMLElement $complexType
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function parseComplexType(Config $config,
                                            XSDMap $XSDMap,
                                            \SimpleXMLElement $complexType,
                                            ClassTemplate $classTemplate)
    {
        self::_parseContent($config, $XSDMap, $complexType, $classTemplate);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $contentElement
     * @param ClassTemplate $classTemplate
     */
    private static function _parseContent(Config $config,
                                          XSDMap $XSDMap,
                                          \SimpleXMLElement $contentElement,
                                          ClassTemplate $classTemplate)
    {
        foreach ($contentElement->children('xs', true) as $element) {
            /** @var \SimpleXMLElement $element */
            switch (strtolower($element->getName())) {
                case ElementTypeEnum::COMPLEX_CONTENT:
                    self::parseComplexContent($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::COMPLEX_TYPE:
                    self::parseComplexType($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_CONTENT:
                    self::parseSimpleContent($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    self::parseSimpleType($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::EXTENSION:
                    self::parseExtension($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    self::parseRestriction($config, $XSDMap, $element, $classTemplate);
                    break;

                case ElementTypeEnum::CHOICE:
                    self::parseChoice($XSDMap, $element, $classTemplate);
                    break;

                default:
                    $config->getLogger()->warning(sprintf(
                        'Unexpected element "%s" seen: %s',
                        $element->getName(),
                        $element
                    ));
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap $XSDMap
     * @param \SimpleXMLElement $complexContent
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function parseComplexContent(Config $config,
                                               XSDMap $XSDMap,
                                               \SimpleXMLElement $complexContent,
                                               ClassTemplate $classTemplate)
    {
        self::_parseContent($config, $XSDMap, $complexContent, $classTemplate);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap $XSDMap
     * @param \SimpleXMLElement $simpleContent
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function parseSimpleContent(Config $config,
                                              XSDMap $XSDMap,
                                              \SimpleXMLElement $simpleContent,
                                              ClassTemplate $classTemplate)
    {
        self::_parseContent($config, $XSDMap, $simpleContent, $classTemplate);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $simpleType
     * @param ClassTemplate $classTemplate
     */
    public static function parseSimpleType(Config $config,
                                           XSDMap $XSDMap,
                                           \SimpleXMLElement $simpleType,
                                           ClassTemplate $classTemplate)
    {
        self::_parseContent($config, $XSDMap, $simpleType, $classTemplate);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $extension
     * @param ClassTemplate $classTemplate
     */
    public static function parseExtension(Config $config,
                                          XSDMap $XSDMap,
                                          \SimpleXMLElement $extension,
                                          ClassTemplate $classTemplate)
    {
        self::determineParentClass($config, $XSDMap, $extension, $classTemplate);
        self::_implementProperties($config, $XSDMap, $extension, $classTemplate);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sxe
     * @param ClassTemplate $classTemplate
     */
    public static function determineParentClass(Config $config,
                                                XSDMap $XSDMap,
                                                \SimpleXMLElement $sxe,
                                                ClassTemplate $classTemplate)
    {
        $fhirElementName = XMLUtils::getBaseFHIRElementNameFromExtension($sxe);
        if (null === $fhirElementName) {
            $fhirElementName = XMLUtils::getBaseFHIRElementNameFromRestriction($sxe);
        }

        if (null === $fhirElementName) {
            return;
        }

        if (0 === strpos($fhirElementName, 'xs')) {
            $fhirElementName = substr($fhirElementName, 3);
        }

        self::findParentElementXSDMapEntry($fhirElementName, $XSDMap, $classTemplate);
    }

    /**
     * @param string $fhirElementName
     * @param XSDMap $XSDMap
     * @param ClassTemplate $classTemplate
     */
    public static function findParentElementXSDMapEntry($fhirElementName,
                                                        XSDMap $XSDMap,
                                                        ClassTemplate $classTemplate)
    {
        if (isset($XSDMap[$fhirElementName])) {
            $classTemplate->setExtendedElementMapEntry($XSDMap[$fhirElementName]);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $sxe
     * @param ClassTemplate $classTemplate
     */
    private static function _implementProperties(Config $config,
                                                 XSDMap $XSDMap,
                                                 \SimpleXMLElement $sxe,
                                                 ClassTemplate $classTemplate)
    {
        foreach ($sxe->children('xs', true) as $element) {
            /** @var \SimpleXMLElement $element */
            switch (strtolower($element->getName())) {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::UNION:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::ENUMERATION:
                    PropertyGenerator::implementProperty($XSDMap, $classTemplate, $element);
                    break;

                default:
                    $config->getLogger()->warning(sprintf(
                        'Unexpected "property" element "%s" found: %s',
                        $element->getName(),
                        $element
                    ));
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param XSDMap $XSDMap
     * @param \SimpleXMLElement $restriction
     * @param ClassTemplate $classTemplate
     */
    public static function parseRestriction(Config $config,
                                            XSDMap $XSDMap,
                                            \SimpleXMLElement $restriction,
                                            ClassTemplate $classTemplate)
    {
        self::determineParentClass($config, $XSDMap, $restriction, $classTemplate);
        self::_implementProperties($config, $XSDMap, $restriction, $classTemplate);
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
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
     */
    public static function addBaseClassProperties(ClassTemplate $classTemplate, XSDMap\XSDMapEntry $mapEntry)
    {
        // Add the source element name to each class...
        $property = new BasePropertyTemplate($mapEntry, new PHPScopeEnum(PHPScopeEnum::_PRIVATE), true, false);
        $property->setDefaultValue($mapEntry->fhirElementName);
        $property->setName('_fhirElementName');
        $property->setPHPType('string');
        $property->setPrimitive(true);
        $classTemplate->addProperty($property);
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function addBaseClassInterfaces(ClassTemplate $classTemplate)
    {
        $classTemplate->addImplementedInterface('\\JsonSerializable');
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function addBaseClassMethods(Config $config, ClassTemplate $classTemplate)
    {
        MethodGenerator::implementConstructor($config, $classTemplate);
        MethodGenerator::implementToString($config, $classTemplate);
        MethodGenerator::implementJsonSerialize($config, $classTemplate);
        MethodGenerator::implementXMLSerialize($config, $classTemplate);
    }
}