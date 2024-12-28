<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class TypeDecorator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorator
{
    /** @var array */
    private static $_dstu1Primitives = ['ResourceType', 'xmlIdRef', 'ResourceNamesPlusBinary'];

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findComponentOfTypes(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();
            if (false === strpos($fhirName, '.')) {
                continue;
            }
            $split = explode('.', $fhirName, 2);
            if ($ptype = $types->getTypeByName($split[0])) {
                $config->getLogger()->debug(
                    sprintf(
                        'Found Parent Component Type "%s" for Component "%s"',
                        $ptype,
                        $type
                    )
                );
                $type->setComponentOfType($ptype);
            } else {
                throw ExceptionUtils::createComponentParentTypeNotFoundException($type);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findRestrictionBaseTypes(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // skip primitive types as they are already as base as they can go
            if (false !== strpos($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
                continue;
            }

            $rbName = $type->getRestrictionBaseFHIRName();

            if (null === $rbName) {
                continue;
            }

            if (0 === strpos($rbName, 'xs:')) {
                $rbName = substr($rbName, 3);
                if ('token' === $rbName || ctype_upper($rbName[0])) {
                    $logger->warning(
                        sprintf(
                            'Type "%s" has restriction base "%s", setting to string...',
                            $fhirName,
                            $rbName
                        )
                    );
                    $rbName = 'string';
                }
                $rbName = "{$rbName}-primitive";
            }

            $rbType = $types->getTypeByName($rbName);

            if (null === $rbType) {
                throw ExceptionUtils::createTypeRestrictionBaseNotFoundException($type);
            }

            $type->setRestrictionBaseFHIRType($rbType);

            $logger->info(
                sprintf(
                    'Type "%s" has restriction base Type "%s"',
                    $type,
                    $rbType
                )
            );
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findParentTypes(VersionConfig $config, Types $types)
    {
        // These are here to enable backwards compatibility with dstu1 and 2
        static $knownDecimal = ['score'];
        static $knownInteger = ['totalResults'];

        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // try to locate parent type name...
            $parentTypeName = $type->getParentTypeName();
            if (null === $parentTypeName) {
                if (in_array($fhirName, $knownDecimal, true)) {
                    $parentTypeName = 'decimal';
                } elseif (in_array($fhirName, $knownInteger, true)) {
                    $parentTypeName = 'integer';
                } elseif ($rbType = $type->getRestrictionBaseFHIRType()) {
                    $parentTypeName = $rbType->getFHIRName();
                } else {
                    continue;
                }
            }

            // skip "base" types 'cuz php.
            if (0 === strpos($parentTypeName, 'xs:')) {
                $logger->warning(
                    sprintf(
                        'Type "%s" has un-resolvable parent "%s"',
                        $type,
                        $parentTypeName
                    )
                );
                continue;
            }

            if ($ptype = $types->getTypeByName($parentTypeName)) {
                $type->setParentType($ptype);
                $logger->info(
                    sprintf(
                        'Type "%s" has parent "%s"',
                        $type,
                        $ptype
                    )
                );
            } else {
                throw ExceptionUtils::createTypeParentNotFoundException($type);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findPropertyTypes(VersionConfig $config, Types $types)
    {
        $log = $config->getLogger();

        foreach ($types->getIterator() as $type) {
            $typeKind = $type->getKind();
            foreach ($type->getProperties()->getIterator() as $property) {
                // handle "value" property on primitive types explicitly
                if ($property->isValueProperty()) {
                    if ($typeKind->isPrimitive()) {
                        $primitiveType = $type->getPrimitiveType();
                        $log->info(
                            sprintf(
                                'Type "%s" Property "%s" as raw PHP value of "%s"',
                                $type->getFHIRName(),
                                $property->getName(),
                                (string)$primitiveType
                            )
                        );
                        $property->setRawPHPValue($primitiveType->getPHPValueType());
                        continue; // move on to next property
                    } elseif ($typeKind->isList()) {
                        $property->setRawPHPValue($type->getParentType()->getPrimitiveType()->getPHPValueType());
                        continue;
                    }
                }

                // everything else

                $valueFHIRTypeName = (string)$property->getValueFHIRTypeName();

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
                        continue; // move on to next property
                    }

                    if (0 === strpos($valueFHIRTypeName, 'xs:')) {
                        $pt = $types->getTypeByName(substr($valueFHIRTypeName, 3) . '-primitive');
                    } elseif (null !== ($refName = $property->getRef())) {
                        $pt = $types->getTypeByName($refName);
                    }
                    if (null === $pt) {
                        throw ExceptionUtils::createUnknownPropertyTypeException($type, $property);
                    }
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
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function determinePrimitiveTypes(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (in_array($type->getFHIRName(), self::$_dstu1Primitives, true)) {
                $ptn = 'string';
            } elseif ($type->getKind()->isPrimitive()) {
                $ptn = $type->getFHIRName();
            } elseif ($type->hasPrimitiveParent()) {
                $ptn = $type->getParentType()->getFHIRName();
            } else {
                continue;
            }
            $ptn = str_replace('-primitive', '', $ptn);
            $pt = new PrimitiveTypeEnum($ptn);
            $type->setPrimitiveType($pt);
            $logger->info(
                sprintf(
                    'Type "%s" is a Primitive of type "%s"',
                    $type,
                    $pt
                )
            );
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function ensureValueOnPrimitiveChildTypes(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (!$type->hasPrimitiveParent() ||
                null !== $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME)) {
                continue;
            }
            $logger->warning(
                sprintf(
                    'Type "%s" extends primitive "%s" but is missing "%s" property.  Adding...',
                    $type->getFHIRName(),
                    $type->getParentType()->getFHIRName(),
                    PHPFHIR_VALUE_PROPERTY_NAME
                )
            );
            $property = new Property($type, $type->getSourceSXE(), $type->getSourceFilename());
            $property->setName(PHPFHIR_VALUE_PROPERTY_NAME);
            $type->getProperties()->addProperty($property);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param string $kindName
     */
    private static function setTypeKind(VersionConfig $config, Types $types, Type $type, $kindName)
    {
        $kind = new TypeKindEnum($kindName);
        $type->setKind($kind);
        $config->getLogger()->info(
            sprintf(
                'Setting Type "%s" to Kind "%s"',
                $type->getFHIRName(),
                $type->getKind()
            )
        );
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    private static function determineParsedTypeKind(VersionConfig $config, Types $types, Type $type)
    {
        $logger = $config->getLogger();
        $fhirName = $type->getFHIRName();

        // there are a few specialty types kinds that are set during the parsing process, most notably for
        // html value types and primitive value types
        if (null !== $type->getKind()) {
            $logger->warning(
                sprintf(
                    'Type "%s" already has Kind "%s", will not set again',
                    $fhirName,
                    $type->getKind()
                )
            );
            return;
        }

        if (false !== strpos($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
            self::setTypeKind($config, $types, $type, TypeKindEnum::PRIMITIVE);
        } elseif (false !== strpos($fhirName, PHPFHIR_LIST_SUFFIX)) {
            // for all intents and purposes, a List type is a multiple choice primitive type
            self::setTypeKind($config, $types, $type, TypeKindEnum::_LIST);
        } elseif (false !== strpos($fhirName, '.') && TypeKindEnum::RESOURCE_INLINE !== $fhirName) {
            // This block indicates the type is only present as the child of a Resource.  Its name may (and in many
            // cases does) conflict with a top level Element or Resource.  Because of this, they are treated differently
            // and must be marked as such.
            self::setTypeKind($config, $types, $type, TypeKindEnum::RESOURCE_COMPONENT);
        } elseif ($types->getTypeByName("{$fhirName}-primitive")) {
            self::setTypeKind($config, $types, $type, TypeKindEnum::PRIMITIVE_CONTAINER);
        } elseif (null !== ($rootType = $type->getRootType()) && $rootType !== $type) {
            // this entire block is only hit when generating from DSTU1 sources.

            // DSTU1 is weird.

            if (null === $rootType->getKind()) {
                // ensure root type has kind
                // this is due to the out-of-order loading that is made possible by looking at all xml files, rather
                // than just fhir-all or something.
                self::determineParsedTypeKind($config, $types, $rootType);
            }

            // These are set to primitive through automagic
            // TODO: maybe set to GENERIC?
            if (in_array($fhirName, self::$_dstu1Primitives, true)) {
                $type->setKind(new TypeKindEnum(TypeKindEnum::PRIMITIVE));
                return;
            }

            $rootTypeKind = $rootType->getKind();

            // this final block is necessary as in DSTU1 all Resources extend Elements, so we cannot just use the upper-
            // most parent to determine type as then they would all just be elements.
            $set = false;
            if ($rootTypeKind->isElement() && [] !== ($parentTypes = $type->getParentTypes())) {
                foreach ($parentTypes as $parentType) {
                    if ('Resource' === $parentType->getFHIRName()) {
                        $set = true;
                        self::setTypeKind($config, $types, $type, TypeKindEnum::RESOURCE);
                    }
                }
            }

            if (!$set) {
                self::setTypeKind($config, $types, $type, (string)$rootTypeKind);
            }
        } elseif (TypeKindEnum::isKnownRoot($fhirName)) {
            self::setTypeKind($config, $types, $type, $fhirName);
        } else {
            // this case is only applicable to the DSTU1 type "Binary"
            self::setTypeKind($config, $types, $type, TypeKindEnum::RAW);
        }
    }

    /**
     * This method is specifically designed to determine the "kind" of every type that was successfully
     * parsed from the provided xsd's.  It does NOT handle value or undefined types.
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function determineParsedTypeKinds(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            self::determineParsedTypeKind($config, $types, $type);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findOverloadedProperties(VersionConfig $config, Types $types)
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
    public static function setContainedTypeFlag(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            if ($types->isContainedType($type)) {
                $type->setContainedType(true);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function setValueContainerFlag(VersionConfig $config, Types $types)
    {
        static $skip = [
            TypeKindEnum::PRIMITIVE,
            TypeKindEnum::RAW,
            TypeKindEnum::QUANTITY,
        ];

        foreach ($types->getIterator() as $type) {
            // TODO: handle valueString, valueQuantity, etc. types?

            // skip primitive types and their child types
            if ($type->getKind()->isOneOf($skip) || $type->hasPrimitiveParent()) {
                continue;
            }

            $properties = $type->getProperties();

            // only target types with a single field on them with the name "value"
            if (1 !== count($properties) || !$properties->hasProperty(PHPFHIR_VALUE_PROPERTY_NAME)) {
                continue;
            }

            $property = $properties->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            $propertyType = $property->getValueFHIRType();

            // only target types where the "value" field is itself typed
            if (null === $propertyType) {
                continue;
            }

            $type->setValueContainer(true);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function setCommentContainerFlag(VersionConfig $config, Types $types)
    {
        static $skip = [TypeKindEnum::PRIMITIVE, TypeKindEnum::RAW];
        foreach ($types->getIterator() as $type) {
            $type->setCommentContainer(
                !$type->hasPrimitiveParent() && !$type->getKind()->isOneOf($skip)
            );
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function setMissingPropertyNames(VersionConfig $config, Types $types)
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

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function parseUnionMemberTypes(VersionConfig $config, Types $types)
    {
        $log = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $unionOf = $type->getUnionOf();
            if ([] === $unionOf) {
                continue;
            }
            $extended = false;
            foreach ($unionOf as $v) {
                // attempt to determine if this is a FHIR type
                $utype = $types->getTypeByName($v);
                if (null !== $utype) {
                    if ($extended) {
                        $log->info(
                            sprintf(
                                'Type "%s" has union member "%s" but has already been extended, adding properties...',
                                $type->getFHIRName(),
                                $utype->getFHIRName()
                            )
                        );
                        foreach ($utype->getProperties()->getIterator() as $property) {
                            $type->addProperty(clone $property);
                        }
                    } else {
                        $log->info(
                            sprintf(
                                'Type "%s" has union member "%s", setting it as parent type...',
                                $type->getFHIRName(),
                                $utype->getFHIRName()
                            )
                        );
                        $type->setParentType($utype);
                        $extended = true;
                    }
                }
            }
        }
    }
}