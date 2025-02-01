<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;

class ImportUtils
{
    public static function compileImportStatements(Imports $imports): string
    {
        $stmts = [];
        foreach ($imports->getIterator() as $import) {
            if ($import->requiresImport()) {
                $stmts[] = "use {$import->getFullyQualifiedName(false)};";
            }
        }
        if ([] === $stmts) {
            return '';
        }
        return implode("\n", $stmts) . "\n";
    }

    public static function buildVersionPrimitiveTypeImports(Version $version, Type $type): void
    {
        $imports = $type->getImports();

        $imports
            ->addCoreFileImportsByName(
                PHPFHIR_CLASSNAME_CONSTANTS,
                PHPFHIR_CLASSNAME_VALIDATOR,
            )
            ->addVersionCoreFileImportsByName(
                $version,
                PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS,
            );

        if (!$type->hasParent()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TRAIT_SOURCE_XMLNS,
                PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE,
            );
        } else {
            $imports->addVersionTypeImports($type->getParentType());
        }
    }

    public static function buildVersionTypeImports(Version $version, Type $type): void
    {
        $logger = $version->getConfig()->getLogger();

        $logger->debug(sprintf('Compiling imports for Type "%s"...', $type->getFHIRName()));

        $sourceMeta = $version->getSourceMetadata();

        $imports = $type->getImports();

        // immediately add self
        $imports->addImport($type->getFullyQualifiedNamespace(false), $type->getClassName());

        // xhtml type has too many special cases, do better.
        if ($type->getFHIRName() === PHPFHIR_XHTML_TYPE_NAME) {
            return;
        }

        if ($type->isPrimitiveOrListType() || $type->hasPrimitiveOrListParent()) {
            self::buildVersionPrimitiveTypeImports($version, $type);
            return;
        }

        $typeKind = $type->getKind();

        if (!$type->isAbstract() && !$type->isPrimitiveOrListType()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_ENCODING_CLASSNAME_XML_WRITER,
                PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG,
                PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG,
            );
        }

        foreach ($type->getDirectlyImplementedInterfaces() as $interface => $namespace) {
            $imports->addImport($namespace, $interface);
        }

        foreach ($type->getDirectlyUsedTraits() as $trait => $namespace) {
            $imports->addImport($namespace, $trait);
        }

        if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE,
            );
        } else if ($type->isResourceType() || $type->hasResourceTypeParent()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE,
            );
        } else if (!$sourceMeta->isDSTU1()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE,
            );
        }

        $imports->addVersionCoreFileImportsByName(
            $type->getVersion(),
            PHPFHIR_VERSION_CLASSNAME_VERSION,
            PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS,
        );

        if (($type->isCommentContainer() && !$type->hasCommentContainerParent()) ||
            $type->hasPropertiesWithValidations() ||
            ($typeKind->isOneOf(TypeKindEnum::PRIMITIVE) && !$type->hasPrimitiveOrListParent())) {
            $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_CONSTANTS);
        }

        if ($sourceMeta->isDSTU1()) {
            $imports->addCoreFileImportsByName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
        }

        if ($typeKind->isResourceContainer($type->getVersion())) {
            $imports->addVersionCoreFileImportsByName(
                $type->getVersion(),
                PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP,
                PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE,
            );
            return;
        }

        $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_VALIDATOR);

        if ($parentType = $type->getParentType()) {
            $imports->addImport(
                $parentType->getFullyQualifiedNamespace(false),
                $parentType->getClassName(),
            );
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

            if ($property->isSerializableAsXMLAttribute()) {
                $imports->addCoreFileImportsByName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);
            }

            $ptk = $propertyType->getKind();

//            if (null !== $property->getOverloadedProperty() && !$ptk->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) {
//                continue;
//            }

            if ($ptk->isResourceContainer($type->getVersion())) {
                $containerType = $version->getDefinition()->getTypes()->getContainerType();
                $imports->addImport(
                    $containerType->getFullyQualifiedNamespace(false),
                    $containerType->getClassName(),
                );

                $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_CONSTANTS);
                $imports->addVersionCoreFileImportsByName($type->getVersion(), PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);
                $imports->addVersionCoreFileImportsByName($type->getVersion(), PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP);
                $imports->addVersionCoreFileImportsByName($type->getVersion(), PHPFHIR_VERSION_CLASSNAME_VERSION);
            } else {
                $valProp = match (true) {
                    $propertyType->isPrimitiveContainer() => $propertyType->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME),
                    $propertyType->hasPrimitiveContainerParent() => $propertyType->getParentProperty(PHPFHIR_VALUE_PROPERTY_NAME),
                    default => null,
                };

                if (null !== $valProp) {
                    $valType = $valProp->getValueFHIRType();
                    $imports->addImport($valType->getFullyQualifiedNamespace(false), $valType->getClassName());
                }

                $imports->addImport(
                    $propertyType->getFullyQualifiedNamespace(false),
                    $propertyType->getClassName(),
                );
            }
        }
    }
}