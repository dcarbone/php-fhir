<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Builder;

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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Version\Definition\Type;

class Imports implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $_config;
    /** @var string */
    private string $_localNamespace;
    /** @var string */
    private string $_localName;

    /** @var \DCarbone\PHPFHIR\Builder\Import[] */
    private array $_imports = [];

    /** @var int */
    private int $_requiredImportCount = 0;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $localNamespace Fully qualified namespace of local entity
     * @param string $localName Name of local entity (class, interface, enum, trait, etc.)
     */
    public function __construct(Config $config, string $localNamespace, string $localName)
    {
        $this->_config = $config;
        $this->_localNamespace = trim($localNamespace, PHPFHIR_NAMESPACE_SEPARATOR);
        $this->_localName = $localName;
    }

    /**
     * @param string $namespace Namespace of referenced entity
     * @param string $name Name of referenced entity
     * @return \DCarbone\PHPFHIR\Builder\Imports
     */
    public function addImport(string $namespace, string $name): self
    {
        // ensure clean namespace value
        $namespace = trim($namespace, PHPFHIR_NAMESPACE_SEPARATOR);

        // do not need to import sibling entities.
        $requiresImport = ($namespace !== $this->_localNamespace);

        if (isset($this->_imports[$name])) {
            // if we have already seen this type, move on.
            if ($this->_imports[$name]->getNamespace() === $namespace) {
                return $this;
            }
            // if there is a conflicting imported type here...
            $aliasName = $this->_findNextAliasName($name);
            $this->_imports[$aliasName] = new Import($name, $namespace, $aliasName, $requiresImport);
        } else if ($name === $this->_localName && $namespace != $this->_localNamespace) {
            // if the referenced type has the same name but exists in a different namespace, alias it.
            $aliasName = $this->_findNextAliasName($name);
            $this->_imports[$aliasName] = new Import($name, $namespace, $aliasName, $requiresImport);
        } else {
            // otherwise, go ahead and add to map.
            $this->_imports[$name] = new Import($name, $namespace, '', $requiresImport);
        }

        if ($requiresImport) {
            $this->_requiredImportCount++;
        }

        uasort(
            $this->_imports,
            function (Import $a, Import $b) {
                return strnatcasecmp($a->getFullyQualifiedName(false), $b->getFullyQualifiedName(false));
            }
        );

        return $this;
    }

    public function addCoreFileImports(string ...$entityNames): self
    {
        foreach ($entityNames as $en) {
            $coreFile = $this->_config->getCoreFiles()->getCoreFileByEntityName($en);
            $this->addImport($coreFile->getFullyQualifiedNamespace(false), $coreFile->getEntityName());
        }
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Builder\Import[]
     */
    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->_imports);
    }

    /**
     * @return \Generator<\DCarbone\PHPFHIR\Builder\Import>
     */
    public function getGenerator(): \Generator
    {
        foreach ($this->_imports as $import) {
            yield $import;
        }
    }

    /**
     * @return int
     */
    public function requiredImportCount(): int
    {
        return $this->_requiredImportCount;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Builder\Import|null
     */
    public function getImportByType(Type $type): ?Import
    {
        $fqn = $type->getFullyQualifiedClassName(false);
        foreach ($this->_imports as $import) {
            if ($import->getFullyQualifiedName(false) === $fqn) {
                return $import;
            }
        }
        return null;
    }

    /**
     * @param string $classname
     * @param string $namespace
     * @return \DCarbone\PHPFHIR\Builder\Import|null
     */
    public function getImportByClassAndNamespace(string $classname, string $namespace): null|Import
    {
        foreach ($this->_imports as $import) {
            if ($import->getNamespace() === $namespace && $import->getClassname() === $classname) {
                return $import;
            }
        }
        return null;
    }

    /**
     * @param string $aliasName
     * @return \DCarbone\PHPFHIR\Builder\Import|null
     */
    public function getImportByAlias(string $aliasName): ?Import
    {
        return $this->_imports[$aliasName] ?? null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->_imports);
    }

    /**
     * TODO: come up with better alias scheme...
     *
     * @param string $classname
     * @return string
     */
    private function _findNextAliasName(string $classname): string
    {
        $i = 1;
        $aliasName = "{$classname}{$i}";
        while (null !== $this->getImportByAlias($aliasName)) {
            $aliasName = "{$classname}{++$i}";
        }
        return $aliasName;
    }
}