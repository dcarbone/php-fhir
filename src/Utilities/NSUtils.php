<?php namespace DCarbone\PHPFHIR\Utilities;

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
use DCarbone\PHPFHIR\Enum\SimpleType;

/**
 * Class NSUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class NSUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string|null $classNS
     * @return string
     */
    public static function generateRootNamespace(Config $config, $classNS)
    {
        $outputNS = (string)$config->getOutputNamespace();
        $classNS = (string)$classNS;

        if ('' === $outputNS && '' === $classNS) {
            return '';
        }

        if ('' === $outputNS) {
            return $classNS;
        }

        if ('' === $classNS) {
            return $outputNS;
        }

        return sprintf('%s\\%s', $outputNS, $classNS);
    }

    public static function compileUseStatements(Config $config, Types $types, Type $type)
    {
        $fqns = $type->getFullyQualifiedNamespace(false);

        $imports = [];

        if ($parentType = $type->getParentType()) {
            if (($parentNS = $parentType->getFullyQualifiedNamespace(false)) !== $fqns) {
                $imports[] = $parentType->getFullyQualifiedClassName(false);
                $config->getLogger()->debug(sprintf(
                    'Type %s has parent Type %s, will add use statement.',
                    $type,
                    $parentType
                ));
            }
        }

        foreach ($type->getProperties()->getIterator() as $property) {
            if ($propertyType = $property->getValueType()) {
                if (($propNS = $propertyType->getFullyQualifiedNamespace(false)) !== $fqns) {
                    $imports[] = $propertyType->getFullyQualifiedClassName(false);
                    $config->getLogger()->debug(sprintf(
                        'Type %s Property %s is of Type %s, which has a different root namespace (%s vs %s).  Will add use statement.',
                        $type,
                        $property,
                        $propertyType,
                        $fqns,
                        $propNS
                    ));
                }
            } elseif (Type\Property::TYPE_HTML === $property->getFHIRTypeName()) {
                $imports[] = static::generateRootNamespace($config, 'PHPFHIRHelper');
                $config->getLogger()->debug(sprintf(
                    'Type %s Property %s is of type %s, adding PHPFHIRHelper use statement',
                    $type,
                    $property,
                    $property->getFHIRTypeName()
                ));
            } else {
                $config->getLogger()->debug(sprintf(
                    'Type %s Property %s is a %s, which has no Type associated with it.  Will not add use statement',
                    $type,
                    $property,
                    $property->getFHIRTypeName()
                ));
            }
        }

        $imports = array_unique($imports);
        sort($imports, SORT_NATURAL);

        $stmt = '';
        foreach ($imports as $import) {
            $stmt .= "use {$import};\n";
        }
        return $stmt;

//        $imports = array();
//        if ($this->extendedElementMapEntry) {
//            $imports[] = sprintf(
//                '%s\\%s',
//                $this->extendedElementMapEntry->namespace,
//                $this->extendedElementMapEntry->className
//            );
//        }
//
//        if (count($this->implementedInterfaces) > 0) {
//            foreach ($this->implementedInterfaces as $interface) {
//                $imports[] = $interface;
//            }
//        }
//
//        // TODO: The below may eventually be used for type-hinting.
////        foreach($this->_properties as $property)
////        {
////            $type = $property->getPhpType();
////            if (null === $type)
////                continue;
////
////            $usedClasses[] = $type;
////        }
//
//        $imports = array_count_values(array_merge($this->getImports(), $imports));
//        ksort($imports);
//
//        foreach ($imports as $name => $timesImported) {
//            // Don't import base namespace things.
//            if (0 === strpos($name, '\\') && 1 === substr_count($name, '\\')) {
//                continue;
//            }
//
//            // Don't use yourself, dog...
//            if ($name === $thisClassName) {
//                continue;
//            }
//
//            // If this class is already in the same namespace as this one...
//            $remainder = str_replace(array($thisNamespace, '\\'), '', $name);
//            if (basename($name) === $remainder) {
//                continue;
//            }
//
//            $useStatement = sprintf("%suse %s;\n", $useStatement, ltrim($name, "\\"));
//        }
//
//        return $useStatement;
    }
}