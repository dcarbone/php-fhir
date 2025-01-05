<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

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

        $typeNS = $type->getFullyQualifiedNamespace(false);
        $configNS = $type->getConfig()->getFullyQualifiedName(false);

        $typeKind = $type->getKind();

        $allProperties = $type->getAllPropertiesIndexedIterator();

        if (!$type->isAbstract()) {
            $imports->addCoreFileImports(PHPFHIR_CLASSNAME_XML_WRITER);
            $imports->addCoreFileImports(PHPFHIR_ENUM_XML_LOCATION);
        }

        $imports->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION);
        $imports->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION_CONSTANTS);
        $imports->addCoreFileImports(PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG);
        $imports->addCoreFileImports(PHPFHIR_CLASSNAME_SERIALIZE_CONFIG);

        $imports->addCoreFileImports(PHPFHIR_INTERFACE_TYPE);

        foreach ($type->getDirectlyImplementedInterfaces() as $interface => $namespace) {
            $imports->addImport($namespace, $interface);
        }

        foreach ($type->getDirectlyUsedTraits() as $trait => $namespace) {
            $imports->addImport($namespace, $trait);
        }

        if (($type->isCommentContainer() && !$type->hasCommentContainerParent()) ||
            $type->hasLocalPropertiesWithValidations() ||
            ($typeKind->isOneOf(TypeKindEnum::PRIMITIVE) && !$type->hasPrimitiveParent())) {
            $imports->addCoreFileImports(PHPFHIR_CLASSNAME_CONSTANTS);
        }

        if ($parentType = $type->getParentType()) {
            $pns = $parentType->getFullyQualifiedNamespace(false);
            $imports->addImport($pns, $parentType->getClassName());
        }

        if ($type->hasLocalPropertiesWithValidations()) {
            $imports->addCoreFileImports(PHPFHIR_CLASSNAME_VALIDATOR);
        }

        if ($restrictionBaseType = $type->getRestrictionBaseFHIRType()) {
            $rns = $restrictionBaseType->getFullyQualifiedNamespace(false);
            $imports->addImport($rns, $restrictionBaseType->getClassName());
        }

        foreach ($allProperties as $property) {
            $propertyType = $property->getValueFHIRType();
            if (null === $propertyType) {
                continue;
            }

            $ptk = $propertyType->getKind();

            if ($property->isOverloaded() && !$ptk->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) {
                continue;
            }

            if ($ptk->isOneOf(TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE) &&
                $typeNS !== $configNS) {
                $imports->addCoreFileImports(PHPFHIR_CLASSNAME_CONSTANTS);
                $imports->addVersionCoreFileImport(PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE);
                $imports->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION_TYPE_MAP);
                $imports->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION);
            } else {
                if ($ptk === TypeKindEnum::PRIMITIVE_CONTAINER) {
                    $primType = $propertyType->getLocalProperties()->getProperty('value')->getValueFHIRType();
                    $imports->addImport($primType->getFullyQualifiedNamespace(false), $primType->getClassName());
                }

                $propertyTypeNS = $propertyType->getFullyQualifiedNamespace(false);
                $imports->addImport($propertyTypeNS, $propertyType->getClassName());
            }
        }
    }
}