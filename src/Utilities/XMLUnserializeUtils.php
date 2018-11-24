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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class XMLUnserializeUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class XMLUnserializeUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    protected static function buildDefaultSetter(VersionConfig $config, Type $type, Type\Property $property)
    {
        $propertyType = $property->getValueType();
        if (null === $propertyType) {
            return '';
        }
        $typeName = $type->getClassName();
        $methodName = 'set' . ucfirst($property->getName());
        $propertyName = $property->getName();
        $propertyTypeClassName = $propertyType->getClassName();
        $out = '';
        if ($propertyType->isPrimitive() ||
            $propertyType->hasPrimitiveParent() ||
            $propertyType->isPrimitiveContainer() ||
            $propertyType->hasPrimitiveContainerParent()) {
            $out .= <<<PHP
        if (null !== (\$v = \$attributes->{$propertyName})) {
            \${$typeName}->{$methodName}({$propertyTypeClassName}::xmlUnserialize(\$v));
        } else
PHP;
        } else {
            $out .= "        ";
        }
        $out .= <<<PHP
if (isset(\$children->{$propertyName})) {
            \${$typeName}->{$methodName}({$propertyTypeClassName}::xmlUnserialize(\$children->{$propertyName}));
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
    protected static function buildCollectionSetter(VersionConfig $config, Type $type, Type\Property $property)
    {
        $propertyType = $property->getValueType();
        if (null === $propertyType) {
            return '';
        }
        $typeName = $type->getClassName();
        $methodName = 'add' . ucfirst($property->getName());
        $propertyName = $property->getName();
        $propertyTypeClassName = $propertyType->getClassName();
        $out = <<<PHP
        if (isset(\$children->{$propertyName}) && 0 < count(\$children->{$propertyName})) {
            foreach(\$children->{$propertyName} as \$child) {
                \${$typeName}->{$methodName}({$propertyTypeClassName}::xmlUnserialize(\$child));
            }
        }

PHP;

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(VersionConfig $config, Type $type)
    {
        $out = <<<PHP
    /**
     * @param \SimpleXMLElement|string|null \$sxe
     * @param null|{$type->getFullyQualifiedClassName(true)} \${$type->getClassName()}
     * @return null|{$type->getFullyQualifiedClassName(true)}
     */
    public static function xmlUnserialize(\$sxe = null, {$type->getClassName()} \${$type->getClassName()} = null)
    {
        if (null === \$sxe) {
            return null;
        }
        if (is_string(\$sxe)) {
            \$sxe = new \SimpleXMLElement(\$sxe);
        }
        if (!(\$sxe instanceof \SimpleXMLElement)) {
            throw new \InvalidArgumentException('{$type->getClassName()}::fromXML - Argument 1 expected to be XML string or instance of \SimpleXMLElement, '.gettype(\$sxe).' seen.');
        }
        if (null === \${$type->getClassName()}) {
            
PHP;
        if ($type->hasParent() && null !== $type->getParentType()) {
            $out .= "\${$type->getClassName()} = {$type->getParentType()->getClassName()}::xmlUnserialize(\$sxe, new {$type->getClassName()});\n";
        } else {
            $out .= "\${$type->getClassName()} = new {$type->getClassName()};\n";
        }
        $out .= "        }\n";
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveBody(VersionConfig $config, Type $type)
    {
        $out = <<<PHP
        if (null !== (\$v = \$sxe->attributes()->value)) {
            return \${$type->getClassName()}->setValue((string)\$v);
        }
        if ('' !== (\$v = (string)\$sxe->children()->value)) {
            return \${$type->getClassName()}->setValue(\$v);
        }
        return \${$type->getClassName()};

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildResourceContainerBody(VersionConfig $config, Type $type)
    {
        $typeName = $type->getClassName();

        $out = <<<PHP
        \$children = \$sxe->children();
        if (0 === count(\$children)) {
            return \${$type->getClassName()};
        }

PHP;

        foreach ($type->getProperties()->getSortedIterator() as $i => $property) {
            $propertyType = $property->getValueType();
            if (null === $propertyType) {
                continue;
            }
            $propertyName = $property->getName();
            $methodName = 'set' . ucfirst($propertyName);
            $propertyTypeClassName = $propertyType->getClassName();
            if (0 === $i) {
                $out .= '        ';
            } else {
                $out .= ' else';
            }
            $out .= <<<PHP
if (isset(\$children->{$propertyName})) {
            \${$typeName}->{$methodName}({$propertyTypeClassName}::xmlUnserialize(\$children->{$propertyName}));
        }
PHP;
        }

        $out .= <<<PHP
        return \${$typeName};

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildDefaultBody(VersionConfig $config, Type $type)
    {
        $properties = $type->getProperties();
        $out = "        \$children = \$sxe->children();\n";
        if ($properties->containsPrimitive() || $properties->containsPrimitiveContainer()) {
            $out .= <<<PHP
        \$attributes = \$sxe->attributes();
        if (0 === count(\$children) && 0 === count(\$attributes)) {
            return null;
        }

PHP;
        } else {
            $out .= <<<PHP
        if (0 === count(\$children)) {
            return null;
        }

PHP;

        }

        foreach ($type->getProperties()->getSortedIterator() as $property) {
            if ($property->isCollection()) {
                $out .= static::buildCollectionSetter($config, $type, $property);
            } else {
                $out .= static::buildDefaultSetter($config, $type, $property);
            }
        }
        $out .= <<<PHP
        return \${$type->getClassName()};

PHP;

        return $out;
    }
}