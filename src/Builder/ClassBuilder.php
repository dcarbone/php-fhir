<?php namespace DCarbone\PHPFHIR\Builder;

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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Utilities\ConstructorUtils;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\MethodUtils;
use DCarbone\PHPFHIR\Utilities\NSUtils;
use DCarbone\PHPFHIR\Utilities\PropertyUtils;

/**
 * Class ClassGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function generateTypeClass(Config $config, Types $types, Type $type)
    {
        $out = <<<PHP
<?php

namespace {$type->getFullyQualifiedNamespace(false)};


PHP;

        $out .= CopyrightUtils::getFullPHPFHIRCopyrightComment();
        $out .= "\n\n";
        $out .= NSUtils::compileUseStatements($config, $types, $type);
        if ("\n\n" !== substr($out, -2)) {
            $out .= "\n";
        }
        if ($doc = $type->getDocBlockDocumentationFragment(1)) {
            $out .= "/**\n{$doc} */\n";
        }
        $out .= "class {$type->getClassName()}";
        if ($propType = $type->getParentType()) {
            $out .= " extends {$propType->getClassName()}";
        }
        $out .= ' implements \\JsonSerializable';
        $out .= "\n{\n";

        $out .= <<<PHP
    /**
     * Raw name of FHIR type represented by this class
     * @var string
     */
    private \$_fhirElementName = "{$type->getFHIRName()}";

PHP;

        if ($type->isPrimitive()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildPrimitiveBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $valueProperty = $type->getProperties()->getProperty('value');
            $out .= MethodUtils::createPrimitiveTypeValueSetter($config, $type, $valueProperty);
            $out .= "\n";
            $out .= MethodUtils::createGetter($config, $valueProperty);
            $out .= "\n";
        } elseif ($type->isPrimitiveContainer()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildPrimitiveBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $valueProperty = $type->getProperties()->getProperty('value');
            $out .= MethodUtils::createPrimitiveSetter($config, $type, $valueProperty);
            $out .= "\n";
            $out .= MethodUtils::createGetter($config, $valueProperty);
            $out .= "\n";
        } elseif ($type->isResourceContainer()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildResourceContainerBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $out .= PropertyUtils::buildClassPropertyMethods($config, $types, $type);
        } elseif (!$type->hasPrimitiveParent() && !$type->hasPrimitiveContainerParent()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildDefaultBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $out .= PropertyUtils::buildClassPropertyMethods($config, $types, $type);
        }

        $out .= MethodUtils::buildToString($config, $type);

        return $out . '}';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap $XSDMap
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate
     */
//    public static function buildFHIRElementClassTemplate(Config $config, XSDMap $XSDMap, XSDMap\XSDMapEntry $mapEntry)
//    {
//        $classTemplate = new ClassTemplate(
//            $mapEntry->fhirElementName,
//            $mapEntry->className,
//            $mapEntry->namespace,
//            $mapEntry,
//            ClassTypeUtils::parseComplexClassType($mapEntry->sxe)
//        );
//
//        foreach ($mapEntry->sxe->children('xs', true) as $element) {
//            /** @var \SimpleXMLElement $element */
//
//        }
//
//        self::addBaseClassProperties($classTemplate, $mapEntry);
//
//        foreach ($classTemplate->getProperties() as $propertyTemplate) {
//            MethodGenerator::implementMethodsForProperty($config, $classTemplate, $propertyTemplate);
//        }
//
//        self::addBaseClassInterfaces($classTemplate);
//        self::addBaseClassMethods($config, $classTemplate);
//
//        // TODO: Find better place for this...
//        if ('ResourceContainer' === $classTemplate->getXSDMapEntry()->getFHIRElementName()) {
//            $method = new BaseMethodTemplate($config, 'getResource');
//            $method->setReturnValueType('mixed');
//            $method->addLineToBody('return $this->jsonSerialize();');
//            $classTemplate->addMethod($method);
//        }
//
//        return $classTemplate;
//    }
//
//    /**
//     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
//     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
//     */
//    public static function addBaseClassProperties(ClassTemplate $classTemplate, XSDMap\XSDMapEntry $mapEntry)
//    {
//        // Add the source element name to each class...
//        $property = new BasePropertyTemplate($mapEntry, new PHPScope(PHPScope::_PRIVATE), true, false);
//        $property->setDefaultValue($mapEntry->fhirElementName);
//        $property->setName('_fhirElementName');
//        $property->setPHPType('string');
//        $property->setPrimitive(true);
//        $classTemplate->addProperty($property);
//    }
//
//    /**
//     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
//     */
//    public static function addBaseClassInterfaces(ClassTemplate $classTemplate)
//    {
//        $classTemplate->addImplementedInterface('\\JsonSerializable');
//    }
//
//    /**
//     * @param \DCarbone\PHPFHIR\Config $config
//     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
//     */
//    public static function addBaseClassMethods(Config $config, ClassTemplate $classTemplate)
//    {
//        MethodGenerator::implementConstructor($config, $classTemplate);
//        MethodGenerator::implementToString($config, $classTemplate);
//        MethodGenerator::implementJsonSerialize($config, $classTemplate);
//        MethodGenerator::implementXMLSerialize($config, $classTemplate);
//    }
}