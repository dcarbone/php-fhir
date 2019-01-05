<?php

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Class SetterUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class MethodUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createPrimitiveSetter(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = PropertyUtils::getPropertyPHPTypeName($type, $property);
        $propType = $property->getValueType();
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s setter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }

        $out = <<<PHP
    /**
{$property->getDocBlockDocumentationFragment()}     * @param null|{$phpType}
     * @return \$this
     */
    public function {$methodName} ({$varName})
    {
        if (!({$varName} instanceof {$propType->getClassName()})) {
            {$varName} = new {$propType->getClassName()}({$varName});
        }
        \$this->{$propName} = {$varName};
        return \$this;
    }

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createPrimitiveContainerSetter(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = PropertyUtils::getPropertyPHPTypeName($type, $property);
        $propType = $property->getValueType();
        if (null === $propType) {
            $config->getLogger()->error(sprintf(
                'Cannot create setter for Type %s field %s as it references unknown type %s',
                $type->getFHIRName(),
                $property->getName(),
                $property->getFHIRTypeName()
            ));
            return '';
        }
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s setter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }
        $out = <<<PHP
    /**
{$property->getDocBlockDocumentationFragment()}     * @param null|array|{$phpType}
     * @return \$this
     */
    public function {$methodName}({$varName})
    {
        if (!({$varName} instanceof {$propType->getClassName()})) {
            {$varName} = new {$propType->getClassName()}({$varName});
        }
        \$this->{$propName}
PHP;
        if ($property->isCollection()) {
            $out .= '[]';
        }
        $out .= <<<PHP
 = {$varName};
        return \$this;
    }

PHP;

        return $out;
    }

    /**
     * TODO: Implement value type, pattern, and/or allowable value check(s)
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createPrimitiveTypeValueSetter(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = PropertyUtils::getPropertyPHPTypeName($type, $property);
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s setter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }
        return <<<PHP
    /**
{$property->getDocBlockDocumentationFragment()}     * @param null|{$phpType}
     * @return \$this
     */
    public function {$methodName} ($varName)
    {
        \$this->{$propName} = {$varName};
        return \$this;
    }

PHP;
    }

    /**
     * Used for both DSTU1 Resource.Inline and DSTU2+ ResourceContainer
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createResourceContainerSetter(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $varName = NameUtils::getPropertyVariableName($propName);
        $propType = $property->getValueType();
        $propTypeClass = $propType->getClassName();
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s setter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }

        $out = "    /**\n";
        $out .= $property->getDocBlockDocumentationFragment();
        $out .= "     * @param null|mixed An instance of a FHIRResource or {$propTypeClass}\n";
        $out .= "     * @return \$this\n";
        $out .= "     */\n";
        $out .= "    public function ";
        $out .= $methodName;
        $out .= "({$varName} = null)\n    {\n";
        $out .= <<<PHP
        if (null === {$varName}) {
            return \$this; 
        }

PHP;

        $out .= <<<PHP
        if ({$varName} instanceof FHIRResource){
            {$varName} = new {$propTypeClass}({$varName});
        }
        if (!({$varName} instanceof {$propTypeClass})) {
            throw new \InvalidArgumentException(sprintf(
                '{$type->getClassName()}::{$methodName} - Argument expected to be instanceof FHIRResource, {$propTypeClass}, or null, %s seen',
                gettype({$varName})
            ));
        }

PHP;


        $out .= <<<PHP
        \$this->{$propName}
PHP;
        if ($property->isCollection()) {
            $out .= '[]';
        }
        $out .= " = {$varName};\n";
        $out .= "        return \$this;\n    }\n";

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createHTMLValueSetter(VersionConfig $config, Type $type, Property $property)
    {
        $out = <<<PHP
    /**
     * @param null|string \$innerHTML
     * @return \$this
     */
    public function setInnerHTML(\$innerHTML)
    {
        \$this->innerHTML = \$innerHTML;
        return \$this;
    }

PHP;
        return $out;
    }



    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createDefaultSetter(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $propType = $property->getValueType();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = PropertyUtils::getPropertyPHPTypeName($type, $property);
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s setter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }

        $out = "    /**\n";
        $out .= $property->getDocBlockDocumentationFragment();
        $out .= "     * @param null|{$phpType}\n";
        $out .= "     * @return \$this\n";
        $out .= "     */\n";
        $out .= "    public function ";
        $out .= $methodName;
        $out .= "({$propType->getClassName()} {$varName} = null)\n    {\n";
        $out .= <<<PHP
        if (null === {$varName}) {
            return \$this; 
        }
        \$this->{$propName}
PHP;
        if ($property->isCollection()) {
            $out .= '[]';
        }
        $out .= " = {$varName};\n";
        $out .= "        return \$this;\n    }\n";

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createResourceContainerGetter(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $propType = $property->getValueType();
        $methodName = 'get' . NameUtils::getPropertyMethodName($property->getName());
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s getter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }
        $out = <<<PHP
    /**
{$property->getDocBlockDocumentationFragment()}     * @return null|mixed
     */
    public function {$methodName}()
    {

PHP;
        if ($property->isCollection()) {
            $out .= <<<PHP
        \$resources = [];
        foreach(\$this->{$propName} as \$container) {
            if (\$container instanceof {$propType->getClassName()}) {
                \$resources[] = \$container->jsonSerialize();
            }
        }
        return \$resources;

PHP;
        } else {
            $out .= <<<PHP
        return isset(\$this->{$propName}) ? \$this->{$propName}->jsonSerialize() : null;

PHP;

        }
        return $out . "    }\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createHTMLValueGetter(VersionConfig $config, Type $type, Property $property)
    {
        $out = <<<PHP
    /**
     * @return null|string
     */
    public function getInnerHTML()
    {
        return \$this->innerHTML;
    }

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createDefaultGetter(VersionConfig $config, Type $type, Property $property)
    {
        $collection = $property->isCollection() ? '[]' : '';
        $methodName = 'get' . NameUtils::getPropertyMethodName($property->getName());
        if (!NameUtils::isValidFunctionName($methodName)) {
            throw new \LogicException(sprintf(
                'Type %s Property %s setter func name %s is not valid',
                $type,
                $property,
                $methodName
            ));
        }

        $phpType = PropertyUtils::getPropertyPHPTypeName($type, $property);

        return <<<PHP
    /**
{$property->getDocBlockDocumentationFragment()}     * @return null|{$phpType}{$collection}
     */
    public function {$methodName}()
    {
        return \$this->{$property->getName()};
    }

PHP;

    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildToString(VersionConfig $config, Type $type)
    {
        $typeKind = $type->getKind();

        $out = <<<PHP
    /**
     * @return string
     */
    public function __toString()
    {
        return
PHP;

        if ($typeKind->isPrimitive() || $type->isPrimitiveContainer()) {
            $out .= " (string)\$this->getValue();";
        } elseif ($typeKind->isResourceContainer() || $typeKind->isInlineResource()) {
            $out .= " (string)\$this->jsonSerialize();";
        } else {
            $out .= " (string)self::FHIR_TYPE_NAME;";
        }

        return $out . "\n    }\n";
    }
}