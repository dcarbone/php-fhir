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
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;

class ImportUtils
{
    public static function compileImportStatements(Imports $imports): string
    {
        $stmts = [];
        foreach ($imports->getIterator() as $import) {
            if ($import->requiresImport()) {

                $stmts[] = $import->getUseStatement();
            }
        }
        if ([] === $stmts) {
            return '';
        }
        return implode("\n", $stmts) . "\n";
    }

    public static function buildPropertyValidationRuleImports(Type $type, Property $property): void
    {
        $imports = $type->getImports();

        foreach ($property->buildValidationMap($type) as $ruleClass => $_) {
            $imports->addCoreFileImportsByName($ruleClass);
        }
    }

    public static function buildTypePropertiesImports(Version $version, Type $type): void
    {
        $imports = $type->getImports();

        foreach ($type->getAllPropertiesIndexedIterator() as $property) {
            self::buildPropertyValidationRuleImports($type, $property);

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

            if ($ptk->isResourceContainer($version)) {
                $containerType = $version->getDefinition()->getTypes()->getContainerType();
                $imports->addImport(
                    $containerType->getFullyQualifiedNamespace(false),
                    $containerType->getClassName(),
                );

                $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_CONSTANTS);
                $imports->addVersionCoreFileImportsByName(
                    $version,
                    PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE,
                    PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP,
                    PHPFHIR_VERSION_CLASSNAME_VERSION,
                );
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

    public static function buildVersionPrimitiveTypeImports(Version $version, Type $type): void
    {
        $imports = $type->getImports();

        $imports
            ->addVersionCoreFileImportsByName(
                $version,
                PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS,
            );

        if (!$type->hasParent()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE,
            );
        } else {
            $imports->addVersionTypeImports($type->getParentType());
        }

        self::buildTypePropertiesImports($version, $type);
    }

    public static function buildVersionTypeImports(Version $version, Type $type): void
    {
        $logger = $version->getConfig()->getLogger();

        $logger->debug(sprintf('Compiling imports for Type "%s"...', $type->getFHIRName()));

        $sourceMeta = $version->getSourceMetadata();

        $imports = $type->getImports();

        // immediately add self
        $imports->addImport($type->getFullyQualifiedNamespace(false), $type->getClassName());

        // few types are handled different.  this is lazy and I hate it, but here we are.
        if ($type->getFHIRName() === PHPFHIR_XHTML_TYPE_NAME || $type->getKind()->isResourceContainer($version)) {
            return;
        }

        // be sure to import directly used interfaces and traits
        foreach ($type->getDirectlyImplementedInterfaces() as $interface => $namespace) {
            $imports->addImport($namespace, $interface);
        }
        foreach ($type->getDirectlyUsedTraits() as $trait => $namespace) {
            $imports->addImport($namespace, $trait);
        }

        // handle primitives in separate func
        if ($type->isPrimitiveType() || $type->hasPrimitiveTypeParent()) {
            self::buildVersionPrimitiveTypeImports($version, $type);
            return;
        }

        if (!$type->isAbstract()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_ENCODING_CLASSNAME_XML_WRITER,
                PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG,
                PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG,
            );
        }

        if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE,
            );
        } else if ($type->isResourceType() || $type->hasResourceTypeParent()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE,
                PHPFHIR_ENUM_VERSION,
            );
        } else if (!$sourceMeta->isDSTU1()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE,
            );
        }

        $imports->addVersionCoreFileImportsByName(
            $type->getVersion(),
            PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS,
        );

        if (($type->isCommentContainer() && !$type->hasCommentContainerParent()) || $type->hasPropertiesWithValidations()) {
            $imports->addCoreFileImportsByName(PHPFHIR_CLASSNAME_CONSTANTS);
        }

        if ($sourceMeta->isDSTU1()) {
            $imports->addCoreFileImportsByName(
                PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE,
            );
            $imports->addVersionCoreFileImportsByName(
                $version,
                PHPFHIR_VERSION_CLASSNAME_VERSION,
            );
        }

        if ($type->getKind()->isResourceContainer($type->getVersion())) {
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

        if ($restrictionBaseType = $type->getRestrictionBaseFHIRType()) {
            $imports->addImport(
                $restrictionBaseType->getFullyQualifiedNamespace(false),
                $restrictionBaseType->getClassName(),
            );
        }

        self::buildTypePropertiesImports($version, $type);
    }
}