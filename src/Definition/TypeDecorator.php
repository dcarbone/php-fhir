<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition;

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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Enum\PrimitiveType;
use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class TypeDecorator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorator
{
    /** @var array */
    private const DSTU1_PRIMITIVES = ['ResourceType', 'xmlIdRef', 'ResourceNamesPlusBinary'];

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findComponentOfTypes(VersionConfig $config, Types $types): void
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
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findRestrictionBaseTypes(VersionConfig $config, Types $types): void
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
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findParentTypes(VersionConfig $config, Types $types): void
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
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function determinePrimitiveTypes(VersionConfig $config, Types $types): void
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (in_array($type->getFHIRName(), self::DSTU1_PRIMITIVES, true)) {
                $ptn = PrimitiveType::STRING->value;
                $logger->debug(sprintf('(DSTU1 suppport) Type "%s" determined to be DSTU1 primitive', $type->getFHIRName()));
            } elseif ($type->hasPrimitiveParent()) {
                $ptn = $type->getParentType()->getFHIRName();
                $logger->debug(sprintf('Type "%s" determined to have a primitive parent', $type->getFHIRName()));
            } elseif ($type->getKind() === TypeKind::PRIMITIVE) {
                $ptn = $type->getFHIRName();
                $logger->debug(sprintf('Type "%s" determined to be a primitive itself', $type->getFHIRName()));
            } else {
                $logger->debug(sprintf('Type "%s" determined to not be a primitive', $type->getFHIRName()));
                continue;
            }

            $logger->debug(sprintf('Setting assumed primitive Type "%s" kind to "%s"', $type->getFHIRName(), $ptn));
            $ptn = str_replace('-primitive', '', $ptn);
            $pt = PrimitiveType::from($ptn);
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
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function ensureValueOnPrimitiveChildTypes(VersionConfig $config, Types $types): void
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
     * @param TypeKind|string $kind
     */
    private static function setTypeKind(VersionConfig $config, Types $types, Type $type, TypeKind|string $kind): void
    {
        if (is_string($kind)) {
            $kind = TypeKind::from($kind);
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
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    private static function determineParsedTypeKind(VersionConfig $config, Types $types, Type $type): void
    {
        $logger = $config->getLogger();
        $fhirName = $type->getFHIRName();
        $rootType = $type->getRootType();

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

        // check if this is a known root and determine kind immediately
        if (TypeKind::isKnownRoot($fhirName)) {
            $logger->debug(sprintf('Type "%s" is a known root, setting kind to "%s"', $type->getFHIRName(), $fhirName));
            self::setTypeKind($config, $types, $type, $fhirName);
            return;
        }

        // ensure root type has kind
        // this is due to the out-of-order loading that is made possible by looking at all xml files, rather
        // than just fhir-all or something.
        if ($rootType !== $type && null === $rootType->getKind()) {
            $logger->debug(sprintf('Type "%s" has root Type "%s" with undefined Kind, determining now', $type->getFHIRName(), $rootType->getFHIRName()));
            self::determineParsedTypeKind($config, $types, $rootType);
        }

        // check if this type is a DSTU1-specific primitive
        if (in_array($fhirName, self::DSTU1_PRIMITIVES, true)) {
            $logger->debug(sprintf('Setting Type "%s" kind to "%s"', $type->getFHIRName(), TypeKind::PRIMITIVE->value));
            $type->setKind(TypeKind::PRIMITIVE);
            return;
        }

        // check if type is primitive...
        if (str_contains($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
            $logger->debug(sprintf('Type "%s" has primitive suffix, setting kind to "%s"', $type->getFHIRName(), TypeKind::PRIMITIVE->value));
            self::setTypeKind($config, $types, $type, TypeKind::PRIMITIVE);
            return;
        }

        // check if type is list...
        if (str_contains($fhirName, PHPFHIR_LIST_SUFFIX)) {
            // for all intents and purposes, a List type is a multiple choice primitive type
            $logger->debug(sprintf('Type "%s" has list suffix, setting kind to "%s"', $type->getFHIRName(), TypeKind::_LIST->value));
            self::setTypeKind($config, $types, $type, TypeKind::_LIST);
            return;
        }

        // This block indicates the type is only present as the child of a Resource.  Its name may (and in many
        // cases does) conflict with a top level Element or Resource.  Because of this, they are treated differently
        // and must be marked as such.
        if (str_contains($fhirName, '.') && TypeKind::RESOURCE_INLINE->value !== $fhirName) {
            $logger->debug(sprintf('Type "%s" is not "%s" but has dot in name, setting kind to "%s"', $type->getFHIRName(), TypeKind::RESOURCE_INLINE->value, TypeKind::RESOURCE_COMPONENT->value));
            self::setTypeKind($config, $types, $type, TypeKind::RESOURCE_COMPONENT);
            return;
        }

        // this is for primitive "wrapper" types, e.g. String -> 'string-primitive'
        if (null !== $types->getTypeByName(sprintf('%s-primitive', $fhirName))) {
            $logger->debug(sprintf('Type "%s" has primitive counterpart, setting kind to "%s"', $type->getFHIRName(), TypeKind::PRIMITIVE_CONTAINER->value));
            self::setTypeKind($config, $types, $type, TypeKind::PRIMITIVE_CONTAINER);
            return;
        }

        // next, attempt to determine kind by looking at this type's root type, asuming it is not a root type itself.
        if ($rootType !== $type) {

            $rootTypeKind = $rootType->getKind();

            // this final block is necessary as in DSTU1 all Resources extend Elements, so we cannot just use the upper-
            // most parent to determine type as then they would all just be elements.
            $set = false;
            if ($rootTypeKind === TypeKind::ELEMENT && [] !== ($parentTypes = $type->getParentTypes())) {
                foreach ($parentTypes as $parentType) {
                    if ('Resource' === $parentType->getFHIRName()) {
                        $set = true;
                        $logger->debug(sprintf('(DSTU1 support) Setting Type "%s" kind to "%s"', $type->getFHIRName(), TypeKind::RESOURCE->value));
                        self::setTypeKind($config, $types, $type, TypeKind::RESOURCE);
                        break;
                    }
                }
            }

            if (!$set) {
                $logger->debug(sprintf('Setting Type "%s" kind to root type kind "%s"', $type->getFHIRName(), $rootTypeKind->value));
                self::setTypeKind($config, $types, $type, $rootTypeKind->value);
            }

            return;
        }

        // this is a catchall that may bomb if we encounter new stuff
        $logger->debug(sprintf('Setting Type "%s" kind to itself ("%s")', $type->getFHIRName(), TypeKind::PHPFHIR_XHTML->value));
        self::setTypeKind($config, $types, $type, TypeKind::PHPFHIR_XHTML);
    }

    /**
     * This method is specifically designed to determine the "kind" of every type that was successfully
     * parsed from the provided xsd's.  It does NOT handle value or undefined types.
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function determineParsedTypeKinds(VersionConfig $config, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            self::determineParsedTypeKind($config, $types, $type);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function setContainedTypeFlag(VersionConfig $config, Types $types): void
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
    public static function setValueContainerFlag(VersionConfig $config, Types $types): void
    {
        static $skip = [
            TypeKind::PRIMITIVE,
            TypeKind::PHPFHIR_XHTML,
            TypeKind::QUANTITY,
        ];

        foreach ($types->getIterator() as $type) {
            // TODO: handle valueString, valueQuantity, etc. types?

            // skip primitive types and their child types
            if ($type->getKind()->isOneOf(...$skip) || $type->hasPrimitiveParent()) {
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
    public static function setCommentContainerFlag(VersionConfig $config, Types $types): void
    {
        static $skip = [TypeKind::PRIMITIVE, TypeKind::PHPFHIR_XHTML];
        foreach ($types->getIterator() as $type) {
            $type->setCommentContainer(
                !$type->hasPrimitiveParent() && !$type->getKind()->isOneOf(...$skip)
            );
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function parseUnionMemberTypes(VersionConfig $config, Types $types): void
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
                        foreach ($utype->getProperties()->allPropertiesIterator() as $property) {
                            $type->getProperties()->addProperty(clone $property);
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