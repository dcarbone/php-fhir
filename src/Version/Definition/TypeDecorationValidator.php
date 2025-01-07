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
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version;

/**
 * Class TypeDecorationValidator
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class TypeDecorationValidator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     */
    public static function validateDecoration(Config $config, Version $version, Types $types): void
    {
        if (!NameUtils::isValidNSName($version->getFullyQualifiedName(false))) {
            throw ExceptionUtils::createInvalidVersionNamespaceException($version);
        }
        
        $seenClasses = [];
        foreach ($types->getGenerator() as $type) {
            $typeKind = $type->getKind();

            if ($type->isRootType()) {
                if (null !== ($parentType = $type->getParentType())) {
                    throw ExceptionUtils::createRootTypeCannotHaveParentException($type, $parentType);
                }
            } elseif (null === $type->getParentType()) {
                throw ExceptionUtils::createNonRootTypeMustHaveParentException($type);
            }

            $fqns = $type->getFullyQualifiedNamespace(false);
            if (!NameUtils::isValidNSName($fqns)) {
                throw ExceptionUtils::createInvalidTypeNamespaceException($type);
            }

            $typeClassName = $type->getClassName();
            if (!NameUtils::isValidClassName($typeClassName)) {
                throw ExceptionUtils::createInvalidTypeClassNameException($type);
            }

            if (null === $typeKind) {
                throw ExceptionUtils::createUnknownTypeKindException($type);
            }

            if ($typeKind === TypeKindEnum::PRIMITIVE) {
                if (null ===  $type->getPrimitiveType()) {
                    throw ExceptionUtils::createUnknownPrimitiveTypeException($type);
                }
            }

            if ($typeKind === TypeKindEnum::LIST) {
                $rbType = $type->getRestrictionBaseFHIRType();
                if (null === $rbType) {
                    throw ExceptionUtils::createUndefinedListRestrictionBaseException($type);
                }
            }

            if ($typeKind === TypeKindenum::PRIMITIVE_CONTAINER) {
                $valueProperty = $type->getProperties()->getProperty('value');
                if (null === $valueProperty) {
                    throw ExceptionUtils::createPrimitiveValuePropertyNotFound($type);
                }
            }

            if ($types->isContainedType($type) !== $type->isContainedType()) {
                throw ExceptionUtils::createContainedTypeFlagMismatchException($types->isContainedType($type), $type);
            }

            foreach ($type->getProperties()->getGenerator() as $property) {
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