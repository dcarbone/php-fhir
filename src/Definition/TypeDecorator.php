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
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class TypeDecorator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorator
{
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
                $config->getLogger()->debug(sprintf(
                    'Found Parent Component Type "%s" for Component "%s"',
                    $ptype,
                    $type
                ));
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
            if (false !== strpos($type->getFHIRName(), PHPFHIR_PRIMITIVE_SUFFIX)) {
                continue;
            }

            $fhirName = $type->getFHIRName();
            $rbName = $type->getRestrictionBaseFHIRName();

            if (null === $rbName) {
                if ('ResourceType' === $fhirName) {
                    $type->setRestrictionBaseFHIRType($types->getTypeByName('string-primitive'));
                }
                continue;
            }

            $rbType = $types->getTypeByName($rbName);
            if (null === $rbType && 0 === strpos($rbName, 'xs:')) {
                if ('xs:token' === $rbName) {
                    $rbType = $types->getTypeByName('string-primitive');
                } else {
                    $sub = substr($rbName, 3);
                    // if the value is uppercase, more than likely is some base xml stuff i'm not gonna mess with.
                    if (ctype_upper($sub[0])) {
                        $logger->warning(sprintf(
                            'Type "%s" has restriction base "%s", skipping lookup...',
                            $fhirName,
                            $rbName
                        ));
                        continue;
                    }
                    $rbType = $types->getTypeByName("{$sub}-primitive");
                }
            }
            if (null === $rbType) {
                throw ExceptionUtils::createTypeRestrictionBaseNotFoundException($type);
            }
            $type->setRestrictionBaseFHIRType($rbType);
            $logger->info(sprintf(
                'Type "%s" has restriction base Type "%s"',
                $type,
                $rbType
            ));
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
        static $knownList = ['ResourceType'];

        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // TODO: this is kinda hacky...
            if (false !== strpos($fhirName, '-list') || in_array($type->getFHIRName(), $knownList, true)) {
                continue;
            }

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
                $logger->warning(sprintf(
                    'Type "%s" has un-resolvable parent "%s"',
                    $type,
                    $parentTypeName
                ));
                continue;
            }

            if ($ptype = $types->getTypeByName($parentTypeName)) {
                $type->setParentType($ptype);
                $logger->info(sprintf(
                    'Type "%s" has parent "%s"',
                    $type,
                    $ptype
                ));
            } else {
                throw ExceptionUtils::createTypeParentNotFoundException($type);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     */
    private static function findPropertyType(VersionConfig $config, Types $types, Type $type, Property $property)
    {
        $logger = $config->getLogger();

        $valueFHIRTypeName = $property->getValueFHIRTypeName();

        $pt = $types->getTypeByName($valueFHIRTypeName);
        if (null === $pt) {
            if (PHPFHIR_XHTML_DIV === $property->getRef()) {
                // TODO: come up with "raw" type for things like this?
                $property->setValueFHIRType($types->getTypeByName('string-primitive'));
                $logger->warning(sprintf(
                    'Type "%s" Property "%s" has Ref "%s", setting Type to "string-primitive"',
                    $type->getFHIRName(),
                    $property->getName(),
                    $property->getRef()
                ));
                return;
            } elseif (0 === strpos($valueFHIRTypeName, 'xs:')) {
                $pt = $types->getTypeByName(substr($valueFHIRTypeName, 3) . '-primitive');
            } elseif (null !== ($refName = $property->getRef())) {
                $pt = $types->getTypeByName($refName);
            }
            if (null === $pt) {
                throw ExceptionUtils::createUnknownPropertyTypeException($type, $property);
            }
        }

        $property->setValueFHIRType($pt);

        $logger->info(sprintf(
            'Type "%s" Property "%s" has Value Type "%s"',
            $type->getFHIRName(),
            $property->getName(),
            $pt->getFHIRName()
        ));

        return;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findPropertyTypes(VersionConfig $config, Types $types)
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
    public static function determinePrimitiveTypes(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            $kind = $type->getKind();
            if ($kind->isPrimitive()) {
                if ($type->hasParent()) {
                    $ptn = $type->getRootType()->getFHIRName();
                } else {
                    $ptn = $type->getFHIRName();
                }
                $ptn = str_replace('-primitive', '', $ptn);
                $pt = new PrimitiveTypeEnum($ptn);
                $type->setPrimitiveType($pt);
                $logger->info(sprintf(
                    'Type "%s" is a Primitive of type "%s"',
                    $type,
                    $pt
                ));
            }
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
        $config->getLogger()->info(sprintf(
            'Setting Type "%s" to Kind "%s"',
            $type->getFHIRName(),
            $type->getKind()
        ));
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    private static function determineParsedTypeKind(VersionConfig $config, Types $types, Type $type)
    {
        // enables backwards compatibility with dstu 1 & 2
        static $knownListTypes = ['ResourceType'];

        $logger = $config->getLogger();
        $fhirName = $type->getFHIRName();

        // there are a few specialty types kinds that are set during the parsing process, most notably for
        // html value types and primitive value types
        if (null !== $type->getKind()) {
            $logger->warning(sprintf(
                'Type "%s" already has Kind "%s", will not set again',
                $fhirName,
                $type->getKind()
            ));
            return;
        }

        if (false !== strpos($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
            self::setTypeKind($config, $types, $type, TypeKindEnum::PRIMITIVE);
        } elseif (false !== strpos($fhirName, PHPFHIR_LIST_SUFFIX) || in_array($fhirName, $knownListTypes, true)) {
            self::setTypeKind($config, $types, $type, TypeKindEnum::_LIST);
        } elseif (false !== strpos($fhirName, '.') && TypeKindEnum::RESOURCE_INLINE !== $fhirName) {
            self::setTypeKind($config, $types, $type, TypeKindEnum::RESOURCE_COMPONENT);
        } elseif ($types->getTypeByName("{$fhirName}-primitive")) {
            self::setTypeKind($config, $types, $type, TypeKindEnum::PRIMITIVE_CONTAINER);
        } elseif (null !== ($rootType = $type->getRootType()) && $rootType !== $type) {
            if (null === $rootType->getKind()) {
                // ensure root type has kind
                self::determineParsedTypeKind($config, $types, $rootType);
            }
            $rootTypeKind = $rootType->getKind();
            if ($rootTypeKind->isPrimitive()) {
                self::setTypeKind($config, $types, $type, (string)TypeKindEnum::GENERIC);
            } else {
                self::setTypeKind($config, $types, $type, (string)$rootTypeKind);
            }
        } elseif (TypeKindEnum::isKnownRoot($fhirName)) {
            self::setTypeKind($config, $types, $type, $fhirName);
        } else {
            self::setTypeKind($config, $types, $type, TypeKindEnum::GENERIC);
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
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    public static function removeDuplicatePropertiesFromType(VersionConfig $config, Types $types, Type $type)
    {
        $parent = $type->getParentType();
        while (null !== $parent) {
            foreach ($type->getProperties()->getIterator() as $property) {
                foreach ($parent->getProperties()->getIterator() as $parentProperty) {
                    if ($property->getName() === $parentProperty->getName()) {
                        $config->getLogger()->warning(sprintf(
                            'Removing Property "%s" from Type "%s" as Parent "%s" already has it',
                            $property,
                            $type,
                            $parent
                        ));
                        $type->getProperties()->removeProperty($property);
                        continue 2;
                    }
                }
            }
            $parent = $parent->getParentType();
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function removeDuplicateProperties(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            if (!$type->hasParent()) {
                continue;
            }
            self::removeDuplicatePropertiesFromType($config, $types, $type);
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
        foreach ($types->getIterator() as $type) {
            foreach ($type->getProperties()->getIterator() as $property) {
                // TODO: handle valueString, valueQuantity, etc. types?
                if ($property->isValueProperty()) {
                    $type->setValueContainer(true);
                    continue 2;
                }
            }
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
                        $log->warning(sprintf(
                            'Setting Type "%s" Property name to "%s"',
                            $type,
                            $newName
                        ));
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
                        $log->info(sprintf(
                            'Type "%s" has union member "%s" but has already been extended, adding properties...',
                            $type->getFHIRName(),
                            $utype->getFHIRName()
                        ));
                        foreach ($utype->getProperties()->getIterator() as $property) {
                            $type->addProperty(clone $property);
                        }
                    } else {
                        $log->info(sprintf(
                            'Type "%s" has union member "%s", setting it as parent type...',
                            $type->getFHIRName(),
                            $utype->getFHIRName()
                        ));
                        $type->setParentType($utype);
                        $extended = true;
                    }
                }
            }
        }
    }
}