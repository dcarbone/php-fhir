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
use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class TypeDecorationValidator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorationValidator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     */
    public static function validateDecoration(VersionConfig $config, Types $types): void
    {
        $versionName = $config->getVersion()->getName();

        $seenClasses = [];
        foreach ($types->getIterator() as $type) {
            $typeKind = $type->getKind();

            if ($type->isRootType()) {
                if (null !== ($parentType = $type->getParentType())) {
                    throw ExceptionUtils::createRootTypeCannotHaveParentException($type, $parentType);
                }
            } elseif (null === $type->getParentType()) {
                throw ExceptionUtils::createNonRootTypeMustHaveParentException($type);
            }

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

            if ($typeKind === TypeKind::PRIMITIVE) {
                if (null ===  $type->getPrimitiveType()) {
                    throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
                }
            }

            if ($typeKind === TypeKind::LIST) {
                $rbType = $type->getRestrictionBaseFHIRType();
                if (null === $rbType) {
                    throw ExceptionUtils::createUndefinedListRestrictionBaseException($type);
                }
            }

            if ($types->isContainedType($versionName, $type) !== $type->isContainedType()) {
                throw ExceptionUtils::createContainedTypeFlagMismatchException($types->isContainedType($versionName, $type), $type);
            }

            foreach ($type->getLocalProperties()->allPropertiesIterator() as $property) {
                $name = $property->getName();
                if (null === $name || '' === $name) {
                    throw ExceptionUtils::createPropertyMissingNameException($type, $property);
                }
            }

            $cname = $type->getFullyQualifiedClassName(false);
            if (isset($seenClasses[$cname])) {
                throw ExceptionUtils::createDuplicateClassException($type);
            } else {
                $seenClasses[$cname] = true;
            }
        }
    }
}