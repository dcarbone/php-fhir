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

use DCarbone\PHPFHIR\Enum\TypeKind;

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
     * @return \DCarbone\PHPFHIR\Version\Definition\TypeImport[]
     */
    public function getIterator(): \Iterator
    {
        $this->buildImports();
        return new \ArrayIterator($this->imports, \ArrayIterator::STD_PROP_LIST);
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
     * @param string $classname
     * @param string $namespace
     */
    private function addImport(string $classname, string $namespace): void
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

    private function buildImports(): void
    {
        if ($this->parsed) {
            return;
        }

        // immediately set to true so we don't recurse ourselves to death.
        $this->parsed = true;

        // immediately add self
        $this->addImport($this->type->getClassName(), $this->type->getFullyQualifiedNamespace(false));

        $typeNS = $this->type->getFullyQualifiedNamespace(false);
        $configNS = $this->type->getConfig()->getFullyQualifiedName(false);
        $versionNS = $this->type->getVersion()->getFullyQualifiedName(false);

        $allProperties = $this->type->getAllPropertiesIterator();

        // non-abstract types must import config and xml writer
        if (!$this->type->isAbstract()) {
            $this->addImport(PHPFHIR_CLASSNAME_CONFIG, $configNS);
            $this->addImport(PHPFHIR_CLASSNAME_XML_WRITER, $configNS);
            $this->addImport(PHPFHIR_ENUM_CONFIG_KEY, $configNS);
            $this->addImport(PHPFHIR_ENUM_XML_LOCATION_ENUM, $configNS);
        }

        // if this type is in a nested namespace, there are  a few base interfaces, classes, and traits
        // that may need to be imported to ensure function
        if ($typeNS !== $configNS) {
            // always add the base interface type as its used by the xml serialization func
            $this->addImport(PHPFHIR_INTERFACE_TYPE, $configNS);
            // always add the constants class as its used everywhere.
            $this->addImport(PHPFHIR_CLASSNAME_VERSION_CONSTANTS, $versionNS);
            // add directly implemented interfaces
            foreach ($this->type->getDirectlyImplementedInterfaces() as $interface) {
                $this->addImport($interface, $configNS);
            }
            // add directly implemented traits
            foreach ($this->type->getDirectlyUsedTraits() as $trait) {
                $this->addImport($trait, $configNS);
            }
        }

        // determine if we need to import our parent type
        if ($parentType = $this->type->getParentType()) {
            $pns = $parentType->getFullyQualifiedNamespace(false);
            $this->addImport($parentType->getClassName(), $pns);
        }

        // determine if we need to import a restriction base
        if ($restrictionBaseType = $this->type->getRestrictionBaseFHIRType()) {
            $rns = $restrictionBaseType->getFullyQualifiedNamespace(false);
            $this->addImport($restrictionBaseType->getClassName(), $rns);
        }

        // add property types to import statement
        foreach ($allProperties as $property) {
            $propertyType = $property->getValueFHIRType();
            if (null === $propertyType) {
                continue;
            }

            $ptk = $propertyType->getKind();

            if ($property->isOverloaded() && !$ptk->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) {
                continue;
            }

            if ($ptk->isOneOf(TypeKind::RESOURCE_CONTAINER, TypeKind::RESOURCE_INLINE) &&
                $typeNS !== $configNS) {
                $this->addImport(PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE, $versionNS);
                $this->addImport(PHPFHIR_CLASSNAME_VERSION_TYPEMAP, $versionNS);
            } else {

                if ($ptk === TypeKind::PRIMITIVE_CONTAINER) {
                    $primType = $propertyType->getLocalProperties()->getProperty('value')->getValueFHIRType();
                    $this->addImport($primType->getClassName(), $primType->getFullyQualifiedNamespace(false));
                }

                $propertyTypeNS = $propertyType->getFullyQualifiedNamespace(false);
                $this->addImport($propertyType->getClassName(), $propertyTypeNS);
            }
        }

        // sort the imported class list
        uasort(
            $this->imports,
            function (TypeImport $a, TypeImport $b) {
                return strnatcasecmp($a->getFullyQualifiedClassname(false), $b->getFullyQualifiedClassname(false));
            }
        );
    }
}