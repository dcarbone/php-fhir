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
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Version;

/**
 * Class TypeDecorator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorator
{
    /** @var array */
    private const DSTU1_PRIMITIVES = ['ResourceType', 'xmlIdRef', 'ResourceNamesPlusBinary'];

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findComponentOfTypes(Config $config, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();
            if (!str_contains($fhirName, '.')) {
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findRestrictionBaseTypes(Config $config, Types $types): void
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // skip primitive types as they are already as base as they can go
            if (str_contains($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
                continue;
            }

            $rbName = $type->getRestrictionBaseFHIRName();

            if (null === $rbName) {
                continue;
            }

            if (str_starts_with($rbName, 'xs:')) {
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
                $rbName = sprintf('%s-primitive', $rbName);
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findParentTypes(Config $config, Types $types): void
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

            // handle base64Binary type
            if ('xs:base64Binary' === $parentTypeName) {
                $parentTypeName = 'base64Binary';
            }

            // skip "base" types 'cuz php
            if (str_starts_with($parentTypeName, 'xs:')) {
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function determinePrimitiveTypes(Config $config, Types $types): void
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (in_array($type->getFHIRName(), self::DSTU1_PRIMITIVES, true)) {
                $ptn = PrimitiveTypeEnum::STRING->value;
                $logger->debug(sprintf('(DSTU1 suppport) Type "%s" determined to be DSTU1 primitive', $type->getFHIRName()));
            } elseif ($type->hasPrimitiveParent()) {
                $ptn = $type->getParentType()->getFHIRName();
                $logger->debug(sprintf('Type "%s" determined to have a primitive parent', $type->getFHIRName()));
            } elseif ($type->getKind() === TypeKindEnum::PRIMITIVE) {
                $ptn = $type->getFHIRName();
                $logger->debug(sprintf('Type "%s" determined to be a primitive itself', $type->getFHIRName()));
            } else {
                $logger->debug(sprintf('Type "%s" determined to not be a primitive', $type->getFHIRName()));
                continue;
            }

            $logger->debug(sprintf('Setting assumed primitive Type "%s" kind to "%s"', $type->getFHIRName(), $ptn));
            $ptn = str_replace('-primitive', '', $ptn);
            $pt = PrimitiveTypeEnum::from($ptn);
            $type->setPrimitiveType($pt);
            $logger->info(
                sprintf(
                    'Type "%s" is a Primitive of type "%s"',
                    $type,
                    $pt->value
                )
            );
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param TypeKindEnum|string $kind
     */
    private static function setTypeKind(Config $config, Types $types, Type $type, TypeKindEnum|string $kind): void
    {
        if (is_string($kind)) {
            $kind = TypeKindEnum::from($kind);
        }
        $type->setKind($kind);
        $config->getLogger()->info(
            sprintf(
                'Setting Type "%s" to Kind "%s"',
                $type->getFHIRName(),
                $type->getKind()->value
            )
        );
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     */
    private static function determineParsedTypeKind(Config $config, Version $version, Types $types, Type $type): void
    {
        $logger = $config->getLogger();
        $fhirName = $type->getFHIRName();

        $versionName = $version->getName();

        // there are a few specialty types kinds that are set during the parsing process, most notably for
        // html value types and primitive value types
        if (null !== $type->getKind()) {
            $logger->warning(
                sprintf(
                    'Type "%s" already has Kind "%s", will not set again',
                    $fhirName,
                    $type->getKind()->value
                )
            );
            return;
        }

        // check if this type is a DSTU1-specific primitive
        if (in_array($fhirName, self::DSTU1_PRIMITIVES, true)) {
            $logger->debug(sprintf('Setting Type "%s" kind to "%s"', $type->getFHIRName(), TypeKindEnum::PRIMITIVE->value));
            $type->setKind(TypeKindEnum::PRIMITIVE);
            return;
        }

        // check if type is primitive...
        if (str_contains($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
            $logger->debug(sprintf('Type "%s" has primitive suffix, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::PRIMITIVE->value));
            self::setTypeKind($config, $types, $type, TypeKindEnum::PRIMITIVE);
            return;
        }

        // if this is a root type, i.e. it has no parents, set its kind to itself
        if ($type->isRootType()) {
            $logger->debug(sprintf('Type "%s" has no parent, setting kind to itself...', $type->getFHIRName()));
            self::setTypeKind($config, $types, $type, $fhirName);
        }

        // otherise, localize root type.
        $rootType = $type->getRootType();

        // if this is the "container" type for this FHIR version
        if (TypeKindEnum::isContainerTypeName($fhirName)) {
            $logger->debug(sprintf('Type "%s" is a container type, setting kind to "%s"', $type->getFHIRName(), $fhirName));
            self::setTypeKind($config, $types, $type, $fhirName);
            return;
        }

        // ensure root type has kind
        // this is due to the out-of-order loading that is made possible by looking at all xml files, rather
        // than just fhir-all or something.
        if ($rootType !== $type && null === $rootType->getKind()) {
            $logger->debug(sprintf('Type "%s" has root Type "%s" with undefined Kind, determining now', $type->getFHIRName(), $rootType->getFHIRName()));
            self::determineParsedTypeKind($config, $version, $types, $rootType);
        }

        // check if type is list...
        if (str_contains($fhirName, PHPFHIR_LIST_SUFFIX)) {
            // for all intents and purposes, a List type is a multiple choice primitive type
            $logger->debug(sprintf('Type "%s" has list suffix, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::LIST->value));
            self::setTypeKind($config, $types, $type, TypeKindEnum::LIST);
            return;
        }

        // This block indicates the type is only present as the child of a Resource.  Its name may (and in many
        // cases does) conflict with a top level Element or Resource.  Because of this, they are treated differently
        // and must be marked as such.
        if (str_contains($fhirName, '.') && TypeKindEnum::RESOURCE_INLINE->value !== $fhirName) {
            $logger->debug(sprintf('Type "%s" is not "%s" but has dot in name, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::RESOURCE_INLINE->value, TypeKindEnum::RESOURCE_COMPONENT->value));
            self::setTypeKind($config, $types, $type, TypeKindEnum::RESOURCE_COMPONENT);
            return;
        }

        // this is for primitive "wrapper" types, e.g. String -> 'string-primitive'
        if (null !== $types->getTypeByName(sprintf('%s-primitive', $fhirName))) {
            $logger->debug(sprintf('Type "%s" has primitive counterpart, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::PRIMITIVE_CONTAINER->value));
            self::setTypeKind($config, $types, $type, TypeKindEnum::PRIMITIVE_CONTAINER);
            return;
        }

        // special block for xhtml type
        if (PHPFHIR_XHTML_TYPE_NAME === $type->getFHIRName()) {
            $logger->debug(sprintf('Setting Type "%s" kind to itself ("%s")', $type->getFHIRName(), TypeKindEnum::PHPFHIR_XHTML->value));
            self::setTypeKind($config, $types, $type, TypeKindEnum::PHPFHIR_XHTML);
            return;
        }

        // otherwise, set kind to that of its parent
        $logger->debug(sprintf('Setting Type "%s" kind to it parent ("%s")', $type->getFHIRName(), $rootType->getKind()->value));
        self::setTypeKind($config, $types, $type, $rootType->getKind());
    }

    /**
     * This method is specifically designed to determine the "kind" of every type that was successfully
     * parsed from the provided xsd's.  It does NOT handle value or undefined types.
     *
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function determineParsedTypeKinds(Config $config, Version $version, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            self::determineParsedTypeKind($config, $version, $types, $type);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function setContainedTypeFlag(Config $config, Version $version, Types $types): void
    {
        $versionName = $version->getName();

        foreach ($types->getIterator() as $type) {
            if ($types->isContainedType($type)) {
                $type->setContainedType(true);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function setValueContainerFlag(Config $config, Types $types): void
    {
        static $skip = [
            TypeKindEnum::PRIMITIVE,
            TypeKindEnum::PHPFHIR_XHTML,
            TypeKindEnum::QUANTITY,
        ];

        foreach ($types->getIterator() as $type) {
            // TODO: handle valueString, valueQuantity, etc. types?

            // skip primitive types and their child types
            if ($type->getKind()->isOneOf(...$skip) || $type->hasPrimitiveParent()) {
                continue;
            }

            $properties = $type->getLocalProperties();

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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function setCommentContainerFlag(Config $config, Types $types): void
    {
        static $skip = [TypeKindEnum::PRIMITIVE, TypeKindEnum::PHPFHIR_XHTML];
        foreach ($types->getIterator() as $type) {
            $type->setCommentContainer(
                !$type->hasPrimitiveParent() && !$type->getKind()->isOneOf(...$skip)
            );
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function parseUnionMemberTypes(Config $config, Types $types): void
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
                        foreach ($utype->getLocalProperties()->getAllPropertiesIterator() as $property) {
                            $type->getLocalProperties()->addProperty(clone $property);
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