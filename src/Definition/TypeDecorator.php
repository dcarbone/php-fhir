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
    public static function findParentTypes(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            if (null !== ($parentTypeName = $type->getParentTypeName())) {
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

    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function findPropertyTypes(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();
        foreach ($types->getIterator() as $type) {
            foreach ($type->getProperties()->getIterator() as $property) {
                if ($pt = $types->getTypeByName($property->getValueFHIRTypeName())) {
                    $property->setValueFHIRType($pt);
                    $logger->info(sprintf(
                        'Type "%s" Property "%s" has Value Type "%s"',
                        $type->getFHIRName(),
                        $property->getName(),
                        $pt->getFHIRName()
                    ));
                } elseif (PHPFHIR_XHTML_DIV === $property->getRef()) {
                    // TODO: do something fancier here...
                    $property->setValueFHIRType($types->getTypeByName('string-primitive'));
                    $logger->warning(sprintf(
                        'Type "%s" Property "%s" has Ref "%s", setting Type to "string-primitive"',
                        $type->getFHIRName(),
                        $property->getName(),
                        $property->getRef()
                    ));
                } else {
                    throw ExceptionUtils::createUnknownPropertyTypeException($type, $property);
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
    public static function determineParsedTypeKinds(VersionConfig $config, Types $types)
    {
        $logger = $config->getLogger();

        foreach ($types->getIterator() as $type) {
            $fhirName = $type->getFHIRName();

            // there are a few specialty types kinds that are set during the parsing process, most notably for
            // html value types and primitive value types
            if (null !== $type->getKind()) {
                $logger->warning(sprintf(
                    'Type %s already has Kind %s, will not set again',
                    $fhirName,
                    $type->getKind()
                ));
                continue;
            }

            // log primitives slightly differently
            if (false !== strpos($fhirName, PHPFHIR_PRIMITIVE_SUFFIX)) {
                $type->setKind(new TypeKindEnum(TypeKindEnum::PRIMITIVE));
                $type->setPrimitiveType(new PrimitiveTypeEnum(str_replace(PHPFHIR_PRIMITIVE_SUFFIX, '', $fhirName)));
                $logger->info(sprintf(
                    'Setting Type "%s" to Kind "%s" with PrimitiveType "%s"',
                    $fhirName,
                    $type->getKind(),
                    $type->getPrimitiveType()
                ));
                continue;
            }

            // everything else
            if (false !== strpos($fhirName, PHPFHIR_LIST_SUFFIX)) {
                $type->setKind(new TypeKindEnum(TypeKindEnum::_LIST));
            } elseif (false !== strpos($type->getFHIRName(), '.')) {
                $type->setKind(new TypeKindEnum(TypeKindEnum::RESOURCE_COMPONENT));
            } elseif ($types->getTypeByName("{$fhirName}-primitive")) {
                $type->setKind(new TypeKindEnum(TypeKindEnum::PRIMITIVE_CONTAINER));
            } elseif (null !== ($rootType = $type->getRootType())) {
                $type->setKind(new TypeKindEnum($rootType->getFHIRName()));
            } else {
                $type->setKind(new TypeKindEnum($fhirName));
            }
            $logger->info(sprintf(
                'Setting Type "%s" to Kind "%s"',
                $fhirName,
                $type->getKind()
            ));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function testDecoration(VersionConfig $config, Types $types)
    {
        foreach ($types->getIterator() as $type) {
            $typeKind = $type->getKind();

            $fqns = $type->getFullyQualifiedNamespace(false);
            if (false === NameUtils::isValidNSName($fqns)) {
                throw ExceptionUtils::createInvalidTypeNamespaceException($type);
            }

            $typeClassName = $type->getClassName();
            if (false === NameUtils::isValidClassName($typeClassName)) {
                throw ExceptionUtils::createInvalidTypeClassNameException($type);
            }

            if (null === $typeKind) {
                throw ExceptionUtils::createUnknownTypeKindException($type);
            }

            if ($typeKind->isPrimitive()) {
                $primitiveType = $type->getPrimitiveType();
                if (null === $primitiveType) {
                    throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
                }
            }
        }
    }
}