<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

abstract class TypePropertyDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @return void
     */
    public static function findPropertyType(Config $config, Types $types, Type $type, Property $property): void
    {
        $log = $config->getLogger();
        $typeKind = $type->getKind();

        // handle primitive and list value properties
        if ($property->isValueProperty()) {
            if ($typeKind === TypeKind::PRIMITIVE) {
                $primitiveType = $type->getPrimitiveType();
                $log->debug(sprintf('Type "%s" is primitive of kind "%s", setting property "%s" raw PHP value type to "%s"', $type->getFHIRName(), $primitiveType->value, $property->getName(), $primitiveType->getPHPValueTypes()));
                $property->setRawPHPValue($primitiveType->getPHPValueTypes());
                return;
            }

            if ($typeKind === TypeKind::LIST) {
                $parentPHPValueType = $type->getParentType()->getPrimitiveType()->getPHPValueTypes();
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
                $property->setValueFHIRType($types->getTypeByName(PHPFHIR_XHTML_TYPE_NAME));
                $log->warning(
                    sprintf(
                        'Type "%s" Property "%s" has Ref "%s", setting Type to "%s"',
                        $type->getFHIRName(),
                        $property->getName(),
                        $property->getRef(),
                        PHPFHIR_XHTML_TYPE_NAME
                    )
                );
                return;
            }

            if (str_starts_with($valueFHIRTypeName, 'xs:')) {
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findPropertyTypes(Config $config, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            foreach ($type->getLocalProperties()->allPropertiesIterator() as $property) {
                self::findPropertyType($config, $types, $type, $property);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findOverloadedProperties(Config $config, Types $types): void
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (!$type->hasParent()) {
                continue;
            }
            $parent = $type->getParentType();
            while (null !== $parent) {
                foreach ($type->getLocalProperties()->allPropertiesIterator() as $property) {
                    $propertyName = $property->getName();
                    foreach ($parent->getLocalProperties()->allPropertiesIterator() as $parentProperty) {
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function setMissingPropertyNames(Config $config, Types $types): void
    {
        $log = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            foreach ($type->getLocalProperties()->allPropertiesIterator() as $property) {
                $propName = $property->getName();
                if ('' === $propName || null === $propName) {
                    $ref = $property->getRef();
                    if (null !== $ref && '' !== $ref) {
                        $newName = $ref;
                        if (str_starts_with($ref, 'xhtml:')) {
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