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
use DCarbone\PHPFHIR\Definition\Types;

/**
 * Class PropertyUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class PropertyUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return bool
     */
    public static function isPropertyImplementedByParent(VersionConfig $config,
                                                         Types $types,
                                                         Type $type,
                                                         Property $property)
    {
        if ($parent = $type->getParentType()) {
            $pName = $property->getName();
            $pType = $property->getFHIRTypeName();
            foreach ($parent->getProperties()->getIterator() as $property) {
                if ($property->getName() === $pName && $property->getFHIRTypeName() === $pType) {
                    return true;
                }
            }
            return static::isPropertyImplementedByParent($config, $types, $parent, $property);
        } else {
            return false;
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @param \DCarbone\PHPFHIR\Definition\Type $valueType
     * @return string
     */
    public static function buildHTMLProperty(VersionConfig $config,
                                             Type $type,
                                             Property $property,
                                             Type $valueType)
    {
        $out = "\n    /**\n";
        $out .= $property->getDocBlockDocumentationFragment();
        $out .= "     * @var string\n";
        $out .= "     */\n";
        $out .= "    private \$innerHTML = null;\n";
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @param \DCarbone\PHPFHIR\Definition\Type $valueType
     * @return string
     */
    public static function buildDefaultProperty(VersionConfig $config, Type $type, Property $property, Type $valueType)
    {
        $phpType = PropertyUtils::getPropertyPHPTypeName($type, $property);
        $out = "\n    /**\n";
        $out .= $property->getDocBlockDocumentationFragment();
        $out .= "     * @var {$phpType}";
        if ($property->isCollection()) {
            $out .= '[]';
        }
        $out .= "\n     */\n";
        $out .= '    private ';
        $out .= NameUtils::getPropertyVariableName($property->getName());
        if ($property->isCollection()) {
            $out .= ' = []';
        } else {
            $out .= ' = null';
        }
        return $out . ";\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildClassPropertyDeclarations(VersionConfig $config, Type $type)
    {
        $out = '';
        foreach ($type->getProperties()->getSortedIterator() as $property) {
            $valueType = $property->getValueType();
            $valueTypeKind = $valueType->getKind();
            if ($valueTypeKind->isHTMLValue()) {
                $out .= self::buildHTMLProperty($config, $type, $property, $valueType);
            } else {
                $out .= self::buildDefaultProperty($config, $type, $property, $valueType);
            }
        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildClassPropertyMethods(VersionConfig $config, Types $types, Type $type)
    {
        $sortedProperties = $type->getProperties()->getSortedIterator();

        $out = '';
        foreach ($sortedProperties as $property) {
            if (static::isPropertyImplementedByParent($config, $types, $type, $property)) {
                continue;
            }
            $propType = $property->getValueType();
            if (null === $propType) {
                throw new \DomainException(sprintf(
                    'Unable to locate FHIR Type for Type %s Property %s',
                    $type,
                    $property
                ));
                continue;
            }

            $propTypeKind = $propType->getKind();

            if ('' !== $out) {
                $out .= "\n";
            }

            if ($propTypeKind->isPrimitive() || $propType->hasPrimitiveParent()) {
                $out .= MethodUtils::createPrimitiveSetter($config, $type, $property);
            } elseif ($propType->isPrimitiveContainer() || $propType->hasPrimitiveContainerParent()) {
                $out .= MethodUtils::createPrimitiveContainerSetter($config, $type, $property);
            } elseif ($propTypeKind->isResourceContainer() || $propTypeKind->isInlineResource()) {
                $out .= MethodUtils::createResourceContainerSetter($config, $type, $property);
            } elseif ($propTypeKind->isHTMLValue()) {
                $out .= MethodUtils::createHTMLValueSetter($config, $type, $property);
            } else {
                $out .= MethodUtils::createDefaultSetter($config, $type, $property);
            }

            $out .= "\n";

            if ($propTypeKind->isResourceContainer()) {
                $out .= MethodUtils::createResourceContainerGetter($config, $type, $property);
            } elseif ($propTypeKind->isHTMLValue()) {
                $out .= MethodUtils::createHTMLValueGetter($config, $type, $property);
            } else {
                $out .= MethodUtils::createDefaultGetter($config, $type, $property);
            }
        }

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function buildPrimitiveJSONMarshalStatement(VersionConfig $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $methodName = 'get' . NameUtils::getPropertyMethodName($propName);
        $out = '';
        if ($property->isCollection()) {
            $out .= <<<PHP
        if (0 < count(\$values = \$this->{$methodName}())) {
            \$vs = [];
            foreach(\$values as \$v) {
                if (null !== \$v) {

PHP;
            if ($config->mustSquashPrimitives()) {
                $out .= <<<PHP
                    if (null !== (\$vv = \$v->getValue())) {
                        \$vs[] = \$vv;
                    }

PHP;
            } else {
                $out .= "                    \$vs[] = \$v;\n";
            }
            $out .= "\n";
            $out .= <<<PHP
                }
            }
            if (0 < count(\$vs)) {
                \$a['{$propName}'] = \$vs;
            }
        }
    }

PHP;
        } elseif ($config->mustSquashPrimitives()) {
            $out .= <<<PHP
        if (null !== (\$v = \$this->{$methodName}()) && null !== (\$vv = \$v->getValue())) {
            \$a['{$propName}'] = \$vv;
        }

PHP;
        } else {
            $out .= <<<PHP
        if (null !== (\$v = \$this->{$methodName}())) {
            \$a['{$propName}'] = \$v;
        }

PHP;
        }

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function buildDefaultJSONMarshalStatement(VersionConfig $config, Type $type, Property $property)
    {
        $out = '';
        $propName = $property->getName();
        $methodName = 'get' . NameUtils::getPropertyMethodName($propName);
        if ($property->isCollection()) {
            $out .= <<<PHP
        if (0 < count(\$values = \$this->{$methodName}())) {
            \$vs = [];
            foreach(\$values as \$value) {
                if (null !== \$value) {
                    \$vs[] = \$value;
                }
            }
            if (0 < count(\$vs)) {
                \$a['{$propName}'] = \$vs;
            }
        }

PHP;
        } else {
            $out .= <<<PHP
        if (null !== (\$v = \$this->{$methodName}())) {
            \$a['{$propName}'] = \$v;
        }

PHP;

        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function getPropertyPHPTypeName(Type $type, Property $property)
    {
        $typeKind = $type->getKind();
        if (($typeKind->isPrimitive() || $type->hasPrimitiveParent()) && 'value' === $property->getName()) {
            // TODO: enable primitive-type specific values here.
            return 'mixed';
        }
        if ($propType = $property->getValueType()) {
            return $propType->getFullyQualifiedClassName(true);
        }
        return 'mixed';
    }
}