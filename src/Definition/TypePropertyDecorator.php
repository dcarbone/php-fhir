<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

abstract class TypePropertyDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @return void
     */
    public static function findPropertyType(VersionConfig $config, Types $types, Type $type, Property $property): void
    {
        $log = $config->getLogger();
        $typeKind = $type->getKind();

        // handle primitive and list value properties
        if ($property->isValueProperty()) {
            if ($typeKind->isPrimitive()) {
                $primitiveType = $type->getPrimitiveType();
                $log->debug(sprintf('Type "%s" is primitive of kind "%s", setting property "%s" raw PHP value type to "%s"', $type->getFHIRName(), $primitiveType, $property->getName(), $primitiveType->getPHPValueType()));
                $property->setRawPHPValue($primitiveType->getPHPValueType());
                return;
            }

            if ($typeKind->isList()) {
                $parentPHPValueType = $type->getParentType()->getPrimitiveType()->getPHPValueType();
                $log->debug(sprintf('Type "%s" is list, setting property "%s" raw PHP value type to "%s"', $type->getFHIRName(), $property->getName(), $parentPHPValueType));
                $property->setRawPHPValue($parentPHPValueType);
                return;
            }
        }

        // everything else

        $valueFHIRTypeName = $property->getValueFHIRTypeName();

        if (null === $valueFHIRTypeName) {
            $log->warning(sprintf('Type "%s" property "%s" did not have "type" attribute', $type->getFHIRName(), $property->getName()));
            $prn = $property->getRef();
            if (null !== $prn) {
                $log->debug(sprintf('Type "%s" property "%s" has ref attribute value "%s", using that', $type->getFHIRName(), $property->getName(), $prn));
                $valueFHIRTypeName = $prn;
            }
        }

        // final check for value type name being null
        if (null === $valueFHIRTypeName) {
            throw ExceptionUtils::createUnknownPropertyTypeException($type, $property);
        }

        $pt = $types->getTypeByName($valueFHIRTypeName);
        if (null === $pt) {
            if (PHPFHIR_XHTML_DIV === $property->getRef()) {
                // TODO: come up with "raw" type for things like this?
                // TODO: XML/HTML values in particular need their own specific type
                $property->setValueFHIRType($types->getTypeByName(PHPFHIR_RAW_TYPE_NAME));
                $log->warning(
                    sprintf(
                        'Type "%s" Property "%s" has Ref "%s", setting Type to "%s"',
                        $type->getFHIRName(),
                        $property->getName(),
                        $property->getRef(),
                        PHPFHIR_RAW_TYPE_NAME
                    )
                );
                return;
            }

            if (0 === strpos($valueFHIRTypeName, 'xs:')) {
                $pt = $types->getTypeByName(substr($valueFHIRTypeName, 3) . '-primitive');
            } elseif (null !== ($refName = $property->getRef())) {
                $pt = $types->getTypeByName($refName);
            }
        }

        // if property type is null at this point, needs fixin'
        if (null === $pt) {
            throw ExceptionUtils::createUnknownPropertyTypeException($type, $property);
        }

        $property->setValueFHIRType($pt);

        $log->info(
            sprintf(
                'Type "%s" Property "%s" has Value Type "%s"',
                $type->getFHIRName(),
                $property->getName(),
                $pt->getFHIRName()
            )
        );
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findPropertyTypes(VersionConfig $config, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            foreach ($type->getProperties()->getIterator() as $property) {
                self::findPropertyType($config, $types, $type, $property);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findOverloadedProperties(VersionConfig $config, Types $types): void
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (!$type->hasParent()) {
                continue;
            }
            $parent = $type->getParentType();
            while (null !== $parent) {
                foreach ($type->getProperties()->getIterator() as $property) {
                    $propertyName = $property->getName();
                    foreach ($parent->getProperties()->getIterator() as $parentProperty) {
                        if ($propertyName === $parentProperty->getName()) {
                            $logger->debug(
                                sprintf(
                                    'Marking Property "%s" on Type "%s" as overloaded as Parent "%s" already has it',
                                    $property,
                                    $type,
                                    $parent
                                )
                            );
                            $property->setOverloaded(true);
                            continue 2;
                        }
                    }
                }
                $parent = $parent->getParentType();
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function setMissingPropertyNames(VersionConfig $config, Types $types): void
    {
        $log = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            foreach ($type->getProperties()->getIterator() as $property) {
                $propName = $property->getName();
                if ('' === $propName || null === $propName) {
                    $ref = $property->getRef();
                    if (null !== $ref && '' !== $ref) {
                        $newName = $ref;
                        if (0 === strpos($ref, 'xhtml:')) {
                            $split = explode(':', $ref, 2);
                            if (2 === count($split) && '' !== $split[1]) {
                                $newName = $split[1];
                            }
                        }
                        $log->warning(
                            sprintf(
                                'Setting Type "%s" Property name to "%s"',
                                $type,
                                $newName
                            )
                        );
                        $property->setName($newName);
                    }
                }
            }
        }
    }
}