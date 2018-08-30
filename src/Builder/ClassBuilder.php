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
            $out .= MethodUtils::createDefaultGetter($config, $valueProperty);
            $out .= "\n";
            $out .= MethodUtils::buildToString($config, $type);
        } elseif (!$type->hasPrimitiveParent() && $type->isPrimitiveContainer()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildPrimitiveContainerBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $valueProperty = $type->getProperties()->getProperty('value');
            $out .= MethodUtils::createPrimitiveSetter($config, $type, $valueProperty);
            $out .= "\n";
            $out .= MethodUtils::createDefaultGetter($config, $valueProperty);
            $out .= "\n";
            $out .= MethodUtils::buildToString($config, $type);
        } elseif ($type->isResourceContainer() || $type->isInlineResource()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildResourceContainerBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $out .= PropertyUtils::buildClassPropertyMethods($config, $types, $type);
            $out .= "\n";
            $out .= MethodUtils::buildToString($config, $type);
        } elseif (!$type->hasPrimitiveParent() && !$type->hasPrimitiveContainerParent()) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= ConstructorUtils::buildHeader($config, $type);
            $out .= ConstructorUtils::buildDefaultBody($config, $type);
            $out .= "    }\n";
            $out .= "\n";
            $out .= PropertyUtils::buildClassPropertyMethods($config, $types, $type);
            $out .= "\n";
            $out .= MethodUtils::buildToString($config, $type);
        }

        return $out . '}';
    }

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