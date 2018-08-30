<?php

namespace DCarbone\PHPFHIR\Utilities;

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
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Definition\Types;

/**
 * Class PropertyUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class PropertyUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return bool
     */
    public static function isPropertyImplementedByParent(Config $config,
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildClassPropertyDeclarations(Config $config, Type $type)
    {
        $out = '';
        foreach ($type->getProperties()->getSortedIterator() as $property) {
            $out .= "\n    /**\n";
            $out .= $property->getDocBlockDocumentationFragment();
            $out .= "     * @var {$property->getPHPTypeName()}";
            if ($property->isCollection()) {
                $out .= '[]';
            }
            $out .= "\n     */\n";
            $out .= '    public ';
            $out .= NameUtils::getPropertyVariableName($property->getName());
            if ($property->isCollection()) {
                $out .= ' = []';
            } else {
                $out .= ' = null';
            }
            $out .= ";\n";
        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildClassPropertyMethods(Config $config, Types $types, Type $type)
    {
        $sortedProperties = $type->getProperties()->getSortedIterator();

        $out = '';
        foreach ($sortedProperties as $property) {
            if (static::isPropertyImplementedByParent($config, $types, $type, $property)) {
                continue;
            }
            $propType = $property->getValueType();
            if (null === $propType) {
                $config->getLogger()->warning(sprintf(
                    'Unable to locate FHIR Type for Type %s Property %s',
                    $type,
                    $property
                ));
                continue;
            }
            if ('' !== $out) {
                $out .= "\n";
            }
            if ($propType->isPrimitive() || $propType->hasPrimitiveParent() || $propType->isPrimitiveContainer() || $propType->hasPrimitiveContainerParent()) {
                $out .= MethodUtils::createPrimitiveSetter($config, $type, $property);
            } elseif ($propType->isResourceContainer() || $propType->isInlineResource()) {
                $out .= MethodUtils::createResourceContainerSetter($config, $type, $property);
            } else {
                $out .= MethodUtils::createDefaultSetter($config, $type, $property);
            }
            $out .= "\n";
            if ($propType->isResourceContainer()) {
                $out .= MethodUtils::createResourceContainerGetter($config, $property);
            } else {
                $out .= MethodUtils::createDefaultGetter($config, $property);
            }
            $out .= "\n";
        }

        return $out;
    }
}