<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * Class TypeImports
 * @package DCarbone\PHPFHIR\Definition
 */
class TypeImports implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Version\Definition\Type */
    private Type $type;

    /** @var \DCarbone\PHPFHIR\Version\Definition\TypeImport[] */
    private array $imports = [];
    /** @var bool */
    private bool $parsed = false;

    /**
     * TypeImports constructor.
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     */
    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @param string $classname
     * @param string $namespace
     */
    public function addImport(string $classname, string $namespace): void
    {
        $requiresImport = !str_starts_with($classname, '\\') &&
            ltrim($namespace, '\\') !== $this->type->getFullyQualifiedNamespace(false);

        if (isset($this->imports[$classname])) {
            // if we have already seen this type, move on.
            if ($this->imports[$classname]->getNamespace() === $namespace) {
                return;
            }

            // if there is a conflicting imported type here...
            $aliasName = $this->findNextAliasName($classname, $namespace);
            $this->imports[$aliasName] = new TypeImport($classname, $namespace, true, $aliasName, $requiresImport);
            return;
        }

        if ($classname === $this->type->getClassName() &&
            $namespace !== $this->type->getFullyQualifiedNamespace(false)) {
            // if the imported type has the same class name as the direct type, but a different namespace
            $aliasName = $this->findNextAliasName($classname, $namespace);
            $this->imports[$aliasName] = new TypeImport($classname, $namespace, true, $aliasName, $requiresImport);
            return;
        }

        // otherwise, go ahead and add to map.
        $this->imports[$classname] = new TypeImport($classname, $namespace, false, '', $requiresImport);
    }

    public function addCoreFileImport(string $entityName): void
    {
        $coreFile = $this->type->getVersion()->getConfig()->getCoreFiles()->getCoreFileByEntityName($entityName);
        $this->addImport($coreFile->getEntityName(), $coreFile->getNamespace());
    }

    public function addVersionCoreFileImport(string $entityName): void
    {
        $coreFile = $this->type->getVersion()->getCoreFiles()->getCoreFileByEntityName($entityName);
        $this->addImport($coreFile->getEntityName(), $coreFile->getNamespace());
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\TypeImport[]
     */
    public function getIterator(): iterable
    {
        $this->buildImports();
        return new \ArrayIterator($this->imports);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\TypeImport|null
     */
    public function getImportByType(Type $type): ?TypeImport
    {
        $this->buildImports();
        $fqn = $type->getFullyQualifiedClassName(false);
        foreach ($this->imports as $import) {
            if ($import->getFullyQualifiedClassname(false) === $fqn) {
                return $import;
            }
        }
        return null;
    }

    /**
     * @param string $classname
     * @param string $namespace
     * @return \DCarbone\PHPFHIR\Version\Definition\TypeImport|null
     */
    public function getImportByClassAndNamespace(string $classname, string $namespace): ?TypeImport
    {
        $this->buildImports();
        foreach ($this->imports as $import) {
            if ($import->getNamespace() === $namespace && $import->getClassname() === $classname) {
                return $import;
            }
        }
        return null;
    }

    /**
     * @param string $aliasName
     * @return \DCarbone\PHPFHIR\Version\Definition\TypeImport|null
     */
    public function getImportByAlias(string $aliasName): ?TypeImport
    {
        $this->buildImports();
        return $this->imports[$aliasName] ?? null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $this->buildImports();
        return count($this->imports);
    }

    /**
     * TODO: come up with better alias scheme...
     *
     * @param string $classname
     * @param string $namespace
     * @return string
     */
    private function findNextAliasName(string $classname, string $namespace): string
    {
        $i = 1;
        $aliasName = "{$classname}{$i}";
        while (null !== $this->getImportByAlias($aliasName)) {
            $aliasName = "{$classname}{++$i}";
        }
        return $aliasName;
    }

    /**
     * Attempts to build succinct list of imports used by this type.  Currently flawed, results in some unused imports
     * to be defined.  Will need to be revisited.
     *
     * @return void
     */
    private function buildImports(): void
    {
        if ($this->parsed) {
            return;
        }
        $this->parsed = true;

        // immediately add self
        $this->addImport($this->type->getClassName(), $this->type->getFullyQualifiedNamespace(false));

        $typeNS = $this->type->getFullyQualifiedNamespace(false);
        $configNS = $this->type->getConfig()->getFullyQualifiedName(false);

        $typeKind = $this->type->getKind();

        $allProperties = $this->type->getAllPropertiesIterator();

        if (!$this->type->isAbstract()) {
            $this->addCoreFileImport(PHPFHIR_CLASSNAME_XML_WRITER);
            $this->addCoreFileImport(PHPFHIR_ENUM_XML_LOCATION);
        }

        $this->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION);
        $this->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION_CONSTANTS);
        $this->addCoreFileImport(PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG);
        $this->addCoreFileImport(PHPFHIR_CLASSNAME_SERIALIZE_CONFIG);

        $this->addCoreFileImport(PHPFHIR_INTERFACE_TYPE);

        foreach ($this->type->getDirectlyImplementedInterfaces() as $interface => $namespace) {
            $this->addImport($interface, $namespace);
        }

        foreach ($this->type->getDirectlyUsedTraits() as $trait => $namespace) {
            $this->addImport($trait, $namespace);
        }

        if (($this->type->isCommentContainer() && !$this->type->hasCommentContainerParent()) ||
            $this->type->hasLocalPropertiesWithValidations() ||
            ($typeKind->isOneOf(TypeKindEnum::PRIMITIVE) && !$this->type->hasPrimitiveParent())) {
            $this->addCoreFileImport(PHPFHIR_CLASSNAME_CONSTANTS);
        }

        if ($parentType = $this->type->getParentType()) {
            $pns = $parentType->getFullyQualifiedNamespace(false);
            $this->addImport($parentType->getClassName(), $pns);
        }

        if ($this->type->hasLocalPropertiesWithValidations()) {
            $this->addCoreFileImport(PHPFHIR_CLASSNAME_VALIDATOR);
        }

        if ($restrictionBaseType = $this->type->getRestrictionBaseFHIRType()) {
            $rns = $restrictionBaseType->getFullyQualifiedNamespace(false);
            $this->addImport($restrictionBaseType->getClassName(), $rns);
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
                $this->addCoreFileImport(PHPFHIR_CLASSNAME_CONSTANTS);
                $this->addVersionCoreFileImport(PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE);
                $this->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION_TYPE_MAP);
                $this->addVersionCoreFileImport(PHPFHIR_CLASSNAME_VERSION);
            } else {
                if ($ptk === TypeKindEnum::PRIMITIVE_CONTAINER) {
                    $primType = $propertyType->getLocalProperties()->getProperty('value')->getValueFHIRType();
                    $this->addImport($primType->getClassName(), $primType->getFullyQualifiedNamespace(false));
                }

                $propertyTypeNS = $propertyType->getFullyQualifiedNamespace(false);
                $this->addImport($propertyType->getClassName(), $propertyTypeNS);
            }
        }

        uasort(
            $this->imports,
            function (TypeImport $a, TypeImport $b) {
                return strnatcasecmp($a->getFullyQualifiedClassname(false), $b->getFullyQualifiedClassname(false));
            }
        );
    }
}