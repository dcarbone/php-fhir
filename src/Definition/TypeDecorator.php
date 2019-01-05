<?php

namespace DCarbone\PHPFHIR\Definition;

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
use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\XMLUtils;

/**
 * Class TypeDecorator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function determineTypeParentName(VersionConfig $config,
                                                   Type $type,
                                                   \SimpleXMLElement $element)
    {
        $parentFHIRName = XMLUtils::getBaseFHIRElementNameFromExtension($element);
        if ($type->getFHIRName() === $parentFHIRName){
            var_dump($type, $element->saveXML());exit;
        }
        if (null === $parentFHIRName) {
            throw new \DomainException(sprintf(
                'Unable to determine parent of Type "%s" from element: %s',
                $type->getFHIRName(),
                $element->saveXML()
            ));
        }
        $type->setParentTypeName($parentFHIRName);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param string $name
     */
    public static function determineComponentOfTypeName(VersionConfig $config, Types $types, Type $type, $name)
    {
        if (false === strpos($name, '.')) {
            $config->getLogger()->error(sprintf(
                '%s called with non-component name %s and Type %s',
                __METHOD__,
                $name,
                $type
            ));
            return;
        }
        $split = explode('.', $name, 2);
        $type->setComponentOfTypeName($split[0]);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findComponentOfTypes(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            if ($tname = $type->getComponentOfTypeName()) {
                if ($ptype = $types->getTypeByFHIRName($tname)) {
                    $config->getLogger()->debug(sprintf(
                        'Found Parent Type %s for Component %s',
                        $ptype,
                        $type
                    ));
                    $type->setComponentOfType($ptype);
                } else {
                    $config->getLogger()->error(sprintf(
                        'Unable to locate Parent Type %s for Component %s',
                        $tname,
                        $type
                    ));
                }
            }

        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findParentTypes(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            if ($parentTypeName = $type->getParentTypeName()) {
                if ($ptype = $types->getTypeByFHIRName($parentTypeName)) {
                    $type->setParentType($ptype);
                    $config->getLogger()->info(sprintf(
                        'Type %s has parent %s',
                        $type,
                        $ptype
                    ));
                }
                $config->getLogger()->info(sprintf(
                    'Unable to locate parent of type %s',
                    $type
                ));
            }
        }

    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findPropertyTypes(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            foreach ($type->getProperties()->getIterator() as $property) {
                $typeKind = $type->getKind();
                // TODO: this is kinda hacky...
                if ('value' === $property->getName() && ($typeKind->isPrimitive() || $type->hasPrimitiveParent())) {
                    // if this is a "value" property from a primitive parent, use the primitive value type
                    $property->setValueType($types->newPrimitiveTypeValueType($property->getName()));
                    $config->getLogger()->info(sprintf(
                        'Setting Type %s Property %s to %s',
                        $type,
                        $property,
                        TypeKind::PRIMITIVE_VALUE
                    ));
                } elseif (false !== strpos($property->getFHIRTypeName(), 'xhtml')) {
                    // if this is an html type...
                    $property->setValueType($types->newHTMLValueType($property->getFHIRTypeName()));
                    $config->getLogger()->notice(sprintf(
                        'Setting Type %s Property %s value Type to %s',
                        $type,
                        $property,
                        TypeKind::HTML_VALUE
                    ));
                } elseif ($pt = $types->getTypeByFHIRName($property->getFHIRTypeName())) {
                    // if this is a "typical" type...
                    $property->setValueType($pt);
                    $config->getLogger()->info(sprintf(
                        'Setting Type %s Property %s to Type %s',
                        $type,
                        $property,
                        $pt
                    ));
                } else {
                    // if we get this far, then there was a type missing from the XSD's
                    $property->setValueType($types->newUndefinedType($property->getName()));
                    $config->getLogger()->warning(sprintf(
                        'Unable to locate Type %s Property %s value Type of %s, using type "%s"',
                        $type,
                        $property,
                        $property->getFHIRTypeName(),
                        TypeKind::UNDEFINED
                    ));
                }
            }
        }
    }

    /**
     * This method is specifically designed to determine the "kind" of every type that was successfully
     * parsed from the provided xsd's.  It does NOT handle value or undefined types.
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function determineParsedTypesKind(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // there are a few specialty types kinds that are set during the parsing process, most notably for
            // html value types and primitive value types
            if (null !== $type->getKind()) {
                $config->getLogger()->info(sprintf(
                    'Type %s already has Kind %s, will not set again',
                    $fhirName,
                    $type->getKind()
                ));
                continue;
            }


            // if this is a child type, use the parent type to determine kind
            if ($rootType = $type->getRootType()) {
                try {
                    $type->setKind(new TypeKind($rootType->getFHIRName()));
                } catch (\UnexpectedValueException $e) {
                    throw new \DomainException(sprintf(
                        'Unable to determine kind for FHIR object %s with root parent of %s',
                        $fhirName,
                        $rootType->getFHIRName()
                    ));
                }
            } else {
                try {
                    $type->setKind(new TypeKind($rootType->getFHIRName()));
                } catch (\UnexpectedValueException $e) {
                    throw new \DomainException(sprintf(
                        'Unable to determine kind for FHIR object %s with no root parent',
                        $fhirName
                    ));
                }
            }
        }
    }
}