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
use DCarbone\PHPFHIR\PropertyExtractor;

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
            ClassTypeUtils::parseComplexClassType($mapEntry->sxe)
        );

        foreach ($mapEntry->sxe->children('xs', true) as $element) {
            /** @var \SimpleXMLElement $element */

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