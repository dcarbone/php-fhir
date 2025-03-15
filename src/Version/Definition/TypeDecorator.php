<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Utilities\ImportUtils;
use DCarbone\PHPFHIR\Version;

/**
 * Class TypeDecorator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorator
{
    /** @var array */
    private const DSTU1_PRIMITIVES = ['ResourceType', 'xmlIdRef', 'ResourceNamesPlusBinary'];

    // These are here to enable backwards compatibility with dstu1 and 2
    private const _KNOWN_DECIMAL = ['score'];
    private const _KNOWN_INTEGER = ['totalResults'];

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findNamelessProperties(Config $config, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            foreach ($type->getProperties()->getIterator() as $property) {
                $name = $property->getName();
                if ('' === $name || null === $name) {
                    $property->setName($property->getRef());
                }
            }
        }
    }

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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function findRestrictionBaseTypes(Version $version, Types $types): void
    {
        $logger = $version->getConfig()->getLogger();
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
                // Found only in the DSTU1 "ResourceNamesPlusBinary" type as a restriction base, "NMTOKEN" is a string
                // without whitespace.  I do not feel like fussing with special logic around this, so force it to be
                // a string.
                if ($version->getSourceMetadata()->isDSTU1() && ('token' === $rbName || ctype_upper($rbName[0]))) {
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
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // try to locate parent type name...
            $parentTypeName = $type->getParentTypeName();
            if (null === $parentTypeName) {
                if (in_array($fhirName, self::_KNOWN_DECIMAL, true)) {
                    $parentTypeName = 'decimal';
                } elseif (in_array($fhirName, self::_KNOWN_INTEGER, true)) {
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
            } elseif ($type->hasPrimitiveTypeParent()) {
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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param TypeKindEnum|string $kind
     */
    private static function setTypeKind(Version $version, Types $types, Type $type, TypeKindEnum|string $kind): void
    {
        if (is_string($kind)) {
            $kind = TypeKindEnum::from($kind);
        }
        $type->setKind($kind);
        $version->getConfig()->getLogger()->info(
            sprintf(
                'Setting Type "%s" to Kind "%s"',
                $type->getFHIRName(),
                $type->getKind()->value
            )
        );
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     */
    private static function determineParsedTypeKind(Version $version, Types $types, Type $type): void
    {
        $logger = $version->getConfig()->getLogger();
        $fhirName = $type->getFHIRName();
        $sourceMeta = $version->getSourceMetadata();

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
        if ($sourceMeta->isDSTU1() && in_array($fhirName, self::DSTU1_PRIMITIVES, true)) {
            $logger->debug(sprintf('Setting Type "%s" kind to "%s"', $type->getFHIRName(), TypeKindEnum::PRIMITIVE->value));
            $type->setKind(TypeKindEnum::PRIMITIVE);
            return;
        }

        // check if type is primitive...
        if (str_ends_with($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
            $logger->debug(sprintf('Type "%s" has primitive suffix, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::PRIMITIVE->value));
            self::setTypeKind($version, $types, $type, TypeKindEnum::PRIMITIVE);
            return;
        }

        // check if type is list...
        if (str_ends_with($fhirName, PHPFHIR_LIST_SUFFIX)) {
            // for all intents and purposes, a List type is a multiple choice primitive type
            $logger->debug(sprintf('Type "%s" has list suffix, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::LIST->value));
            self::setTypeKind($version, $types, $type, TypeKindEnum::LIST);
            return;
        }

        // if this is a root type, i.e. it has no parents, set its kind to itself
        if ($type->isRootType()) {
            $logger->debug(sprintf('Type "%s" has no parent, setting kind to itself...', $type->getFHIRName()));
            self::setTypeKind($version, $types, $type, $fhirName);
        }

        // otherise, localize root type.
        $rootType = $type->getRootType();

        // if this is the "container" type for this FHIR version
        if (TypeKindEnum::isContainerTypeName($version, $fhirName)) {
            $logger->debug(sprintf('Type "%s" is a container type, setting kind to "%s"', $type->getFHIRName(), $fhirName));
            self::setTypeKind($version, $types, $type, $fhirName);
            return;
        }

        // ensure root type has kind
        // this is due to the out-of-order loading that is made possible by looking at all xml files, rather
        // than just fhir-all or something.
        if ($rootType !== $type && null === $rootType->getKind()) {
            $logger->debug(sprintf('Type "%s" has root Type "%s" with undefined Kind, determining now', $type->getFHIRName(), $rootType->getFHIRName()));
            self::determineParsedTypeKind($version, $types, $rootType);
        }

        // This block indicates the type is only present as the child of a Resource.  Its name may (and in many
        // cases does) conflict with a top level Element or Resource.  Because of this, they are treated differently
        // and must be marked as such.
        if (str_contains($fhirName, '.') && !$sourceMeta->isDSTU1() && TypeKindEnum::RESOURCE_INLINE->value !== $fhirName) {
            $logger->debug(sprintf('Type "%s" is not "%s" but has dot in name, setting kind to "%s"', $type->getFHIRName(), TypeKindEnum::RESOURCE_INLINE->value, TypeKindEnum::RESOURCE_COMPONENT->value));
            self::setTypeKind($version, $types, $type, TypeKindEnum::RESOURCE_COMPONENT);
            return;
        }

        // special block for xhtml type
        if (PHPFHIR_XHTML_TYPE_NAME === $type->getFHIRName()) {
            $logger->debug(sprintf('Setting Type "%s" kind to itself ("%s")', $type->getFHIRName(), TypeKindEnum::PHPFHIR_XHTML->value));
            self::setTypeKind($version, $types, $type, TypeKindEnum::PHPFHIR_XHTML);
            return;
        }

        // otherwise, set kind to that of its parent
        $logger->debug(sprintf('Setting Type "%s" kind to it parent ("%s")', $type->getFHIRName(), $rootType->getKind()->value));
        self::setTypeKind($version, $types, $type, $rootType->getKind());
    }

    /**
     * This method is specifically designed to determine the "kind" of every type that was successfully
     * parsed from the provided xsd's.  It does NOT handle value or undefined types.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function determineParsedTypeKinds(Version $version, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            self::determineParsedTypeKind($version, $types, $type);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function setContainedTypeFlag(Config $config, Version $version, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            if ($types->isContainedType($type)) {
                $type->setContainedType(true);
            }
        }
    }

    /**
     * Primitive containers are Element types that contain a "value" element that is, itself, a primitive.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function setPrimitiveContainerFlag(Version $version, Types $types): void
    {
        $logger = $version->getConfig()->getLogger();
        foreach ($types->getIterator() as $type) {
            // skip primitives
            if ($type->isPrimitiveType() || $type->hasPrimitiveTypeParent()) {
                continue;
            }

            // mark types that have a "$n-primitive" counterpart as primitive containers explicitly.
            if (null !== $types->getTypeByName(sprintf('%s-primitive', $type->getFHIRName()))) {
                $logger->debug(sprintf('Type "%s" has primitive counterpart, marking as Primitive Container', $type->getFHIRName()));
                $type->setPrimitiveContainer(true);
                continue;
            }

            // skip types that do not have a directly implmeented "value" property
            $vp = $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            if (null === $vp) {
                continue;
            }

            $vpt = $vp->getValueFHIRType();
            if ($vpt->isPrimitiveType() || $vpt->hasPrimitiveTypeParent()) {
                $logger->debug(sprintf('Type "%s" has primtive "value" property, marking as Primitive Container', $type->getFHIRName()));
                $type->setPrimitiveContainer(true);
            }
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
                !$type->hasPrimitiveTypeParent() && !$type->getKind()->isOneOf(...$skip)
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
                        foreach ($utype->getProperties()->getIterator() as $property) {
                            $type->getProperties()->addOrReturnProperty(clone $property);
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

    protected static function convertDSTU1TypeParent(Version          $version,
                                                     Types            $types,
                                                     string|Type      $base,
                                                     null|string|Type $newParent): void
    {
        if (!$version->getSourceMetadata()->isDSTU1()) {
            throw new \LogicException(sprintf(
                'This method is only intended to be used with DSTU1, version "%s" type seen.',
                $version->getSourceMetadata()->getSemanticVersion(false),
            ));
        }

        if (is_string($base)) {
            $base = $types->getTypeByName($base);
        }
        if (is_string($newParent)) {
            $newParent = $types->getTypeByName($newParent);
        }

        $log = $version->getConfig()->getLogger();

        $baseProperties = $base->getProperties();
        $currentParent = $base->getParentType();

        if (null === $newParent) {
            $log->info(sprintf(
                'Removing DSTU1 type "%s" parent type "%s".',
                $base->getFHIRName(),
                $currentParent->getFHIRName()
            ));
            $base->setKind(TypeKindEnum::from($base->getFHIRName()));
        } else {
            $log->info(sprintf(
                'Changing DSTU1 type "%s" parent from type "%s" to "%s"',
                $base->getFHIRName(),
                $currentParent->getFHIRName(),
                $newParent->getFHIRName(),
            ));
            $base->setKind($newParent->getKind());
        }

        $base->setParentType($newParent);

        foreach ($currentParent->getAllPropertiesIndexedIterator() as $property) {
            // skip properties overloaded in base type.
            $propName = $property->getName();

            // for id properties, we want to make sure that the base Resource type has a primitive container "id"
            // field, but for types extending the base resource we want them to use the parent property.
            if ('id' === $propName) {
                if ($base->hasResourceTypeParent()) {
                    $baseProperties->removePropertyByName('id');
                    continue;
                }
            } else if ($baseProperties->hasProperty($property->getName())) {
                continue;
            }

            $propValueTypeName = $property->getValueFHIRTypeName();
            $propValueType = $property->getValueFHIRType();

            // override resource "id" type with actual id type.
            if ($base->isResourceType() && 'id' === $property->getName()) {
                $propValueType = $types->getTypeByName('id');
                $propValueTypeName = $propValueType->getFHIRName();
            }

            $log->info(sprintf(
                'Copying DSTU1 type "%s" property "%s" to type "%s".',
                $currentParent->getFHIRName(),
                $property->getName(),
                $base->getFHIRName(),
            ));

            $baseProperties->addOrReturnProperty(new Property(
                memberOf: $base,
                sxe: $property->getSourceSXE(),
                sourceFilename: $property->getSourceFilename(),
                name: $property->getName(),
                ref: $property->getRef(),
                use: $property->getUse(),
                minOccurs: $property->getMinOccurs(),
                maxOccurs: $property->getMaxOccurs(),
                valueFHIRTypeName: $propValueTypeName,
                valueFHIRType: $propValueType,
                fixed: $property->getFixed(),
                namespace: $property->getNamespace(),
            ));
        }
    }

    public static function applyVersionOverrides(Version $version, Types $types): void
    {
        $sourceMeta = $version->getSourceMetadata();
        $log = $version->getConfig()->getLogger();

        if ($sourceMeta->isDSTU1()) {
            // this is needed as DSTU1 is a pain in the ass.
            self::convertDSTU1TypeParent($version, $types, 'Resource', null);
            self::convertDSTU1TypeParent($version, $types, 'Binary', 'Resource');
        }

        if ($sourceMeta->isR4B()) {
            // this is needed as the schema for R4B specifies the Resource "id" attribute as merely a string, not an id.
            $resourceType = $types->getTypeByName('Resource');
            $resourceID = $resourceType->getProperties()->getProperty('id');
            $idType = $types->getTypeByName('id');

            $log->info(sprintf(
                'Setting R4B type "%s" property "%s" from type "%s" to type "%s".',
                $resourceType->getFHIRName(),
                $resourceID->getName(),
                $resourceID->getValueFHIRType()->getFHIRName(),
                $idType->getFHIRName(),
            ));

            $resourceID->setValueFHIRType($idType);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function buildTypeImports(Version $version, Types $types): void
    {
        foreach ($types->getIterator() as $type) {
            ImportUtils::buildVersionTypeImports($version, $type);
        }
    }
}