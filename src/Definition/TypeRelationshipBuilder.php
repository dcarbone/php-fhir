<?php

namespace DCarbone\PHPFHIR\Definition;

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
use DCarbone\PHPFHIR\Definition\Type\StandardType;
use DCarbone\PHPFHIR\Utilities\XMLUtils;

/**
 * Class TypeRelator
 * @package DCarbone\PHPFHIR
 */
abstract class TypeRelationshipBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type\StandardType $type
     * @param \SimpleXMLElement $element
     */
    public static function determineTypeParentName(VersionConfig $config,
                                                   StandardType $type,
                                                   \SimpleXMLElement $element)
    {
        $fhirName = XMLUtils::getBaseFHIRElementNameFromExtension($element);
        if (null === $fhirName) {
            $fhirName = XMLUtils::getBaseFHIRElementNameFromRestriction($element);
        }
        if (null !== $fhirName) {
            if (0 === strpos($fhirName, 'xs')) {
                $fhirName = substr($fhirName, 3);
            }
            $type->setParentTypeName($fhirName);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type\StandardType $type
     * @param string $name
     */
    public static function determineComponentOfTypeName(VersionConfig $config, Types $types, StandardType $type, $name)
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
            if ($type->isPrimitive()) {
                continue;
            }
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
                // TODO: this is kinda hacky...
                if ('value' === $property->getName() && ($type->isPrimitive() || $type->hasPrimitiveParent())) {
                    // if this is a "value" property from a primitive parent, use the primitive value type
                    $property->setValueType($types->newPrimitiveTypeValueType($property->getName()));
                    $config->getLogger()->info(sprintf(
                        'Setting Type %s Property %s to %s',
                        $type,
                        $property,
                        PHPFHIR_TYPE_PRIMITIVE_VALUE
                    ));
                } elseif (false !== strpos($property->getFHIRTypeName(), 'xhtml')) {
                    // if this is an html type...
                    $property->setValueType($types->newHTMLType($property->getFHIRTypeName()));
                    $config->getLogger()->notice(sprintf(
                        'Setting Type %s Property %s value Type to %s',
                        $type,
                        $property,
                        PHPFHIR_TYPE_HTML
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
                        PHPFHIR_TYPE_UNDEFINED
                    ));
                }
            }
        }
    }
}