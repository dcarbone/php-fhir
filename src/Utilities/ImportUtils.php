<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Version\Definition\Type;

class ImportUtils
{
    public static function compileImportStatements(Imports $imports): string
    {
        $stmts = [];
        foreach ($imports->getGenerator() as $import) {
            if ($import->requiresImport()) {
                $stmts[] = "use {$import->getFullyQualifiedName(false)};";
            }
        }
        if ([] === $stmts) {
            return '';
        }
        return implode("\n", $stmts) . "\n";
    }

    public static function buildVersionTypeImports(Type $type): void
    {
        $imports = $type->getImports();

        // immediately add self
        $imports->addImport($type->getFullyQualifiedNamespace(false), $type->getClassName());

        $typeKind = $type->getKind();

        if (!$type->isAbstract()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_ENCODING_CLASSNAME_XML_WRITER,
                PHPFHIR_ENCODING_ENUM_XML_LOCATION,
            );
        }

        foreach ($type->getDirectlyImplementedInterfaces() as $interface => $namespace) {
            $imports->addImport($namespace, $interface);
        }

        foreach ($type->getDirectlyUsedTraits() as $trait => $namespace) {
            $imports->addImport($namespace, $trait);
        }

        $imports->addVersionCoreFileImportsByName(
            $type->getVersion(),
            PHPFHIR_VERSION_CLASSNAME_VERSION,
            PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS,
        );

        $imports->addCoreFileImportsByName(
            PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG,
            PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG,
            PHPFHIR_INTERFACE_TYPE,
        );

        if (($type->isCommentContainer() && !$type->hasCommentContainerParent()) ||
            $type->hasLocalPropertiesWithValidations() ||
            ($typeKind->isOneOf(TypeKindEnum::PRIMITIVE) && !$type->hasPrimitiveParent())) {
            $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_CONSTANTS);
        }

        if ($typeKind->isResourceContainer($type->getVersion())) {
            $imports->addVersionCoreFileImportsByName(
                $type->getVersion(),
                PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP,
                PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE,
            );
            return;
        }

        if ($parentType = $type->getParentType()) {
            $imports->addImport(
                $parentType->getFullyQualifiedNamespace(false),
                $parentType->getClassName(),
            );
        }

        if ($type->hasLocalPropertiesWithValidations()) {
            $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_VALIDATOR);
        }

        if ($restrictionBaseType = $type->getRestrictionBaseFHIRType()) {
            $imports->addImport(
                $restrictionBaseType->getFullyQualifiedNamespace(false),
                $restrictionBaseType->getClassName(),
            );
        }

        foreach ($type->getAllPropertiesIndexedIterator() as $property) {
            $propertyType = $property->getValueFHIRType();
            if (null === $propertyType) {
                continue;
            }

            $ptk = $propertyType->getKind();

            if ($property->isOverloaded() && !$ptk->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) {
                continue;
            }

            if ($ptk->isOneOf(TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE)) {
                $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_CONSTANTS);
                $imports->addVersionCoreFileImportsByName($type->getVersion(), PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);
                $imports->addVersionCoreFileImportsByName($type->getVersion(), PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP);
                $imports->addVersionCoreFileImportsByName($type->getVersion(), PHPFHIR_VERSION_CLASSNAME_VERSION);
            } else {
                if ($ptk === TypeKindEnum::PRIMITIVE_CONTAINER) {
                    $primType = $propertyType->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME)->getValueFHIRType();
                    $imports->addImport($primType->getFullyQualifiedNamespace(false), $primType->getClassName());
                }

                $imports->addImport(
                    $propertyType->getFullyQualifiedNamespace(false),
                    $propertyType->getClassName(),
                );
            }
        }
    }
}