<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Builder;

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
use DCarbone\PHPFHIR\CoreFiles\CoreFile;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Version;
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
    /** @var bool */
    private bool $_sorted = false;

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
     * @param null|string $alias Explicit alias to use
     * @return \DCarbone\PHPFHIR\Builder\Import
     */
    public function addImport(string $namespace, string $name, null|string $alias = null): Import
    {
        // ensure clean namespace value
        $namespace = trim($namespace, PHPFHIR_NAMESPACE_SEPARATOR);

        // do not need to explicitly import same-namespace entities.
        $requiresImport = ($namespace !== $this->_localNamespace);
        if ($requiresImport) {
            $this->_requiredImportCount++;
        }

        // check if one with the same name or explicit alias already exists.
        $current = match ($alias) {
            null => $this->_imports[$name] ?? null,
            default => $this->_imports[$alias] ?? null,
        };

        // if match found...
        if ($current) {
            // ...and is an exact match, return it
            if ($current->getNamespace() === $namespace) {
                return $current;
            }

            // otherwise, if alias was explicitly provided, bail out now as this indicates faulty logic somewhere
            // along the line.
            if (null !== $alias) {
                throw new \LogicException(sprintf(
                    'Explicit alias "%s" for type "%s" at namespace "%s" collides with alias for type "%s" at namespace "%s".',
                    $alias,
                    $name,
                    $namespace,
                    $current->getName(),
                    $current->getNamespace(),
                ));
            }

            // otherwise, find next available alias and create import
            $import = new Import($name, $namespace, $this->_findNextAliasName($name), $requiresImport);
        } else if ($name === $this->_localName && $namespace != $this->_localNamespace) {
            // if the referenced type has the same name but exists in a different namespace, alias it.
            $import = new Import($name, $namespace, $alias ?? $this->_findNextAliasName($name), $requiresImport);
        } else {
            // otherwise, go ahead and add to map.
            $import = new Import($name, $namespace, $alias ?? '', $requiresImport);
        }

        // add new import to local map
        $this->_sorted = false;
        $this->_imports[$import->getImportedName()] = $import;

        return $import;
    }

    /**
     * Add specific core file to imported list with optional explicit alias.
     *
     * @param \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile
     * @param string|null $alias
     * @return \DCarbone\PHPFHIR\Builder\Import
     */
    public function addCoreFileImport(CoreFile $coreFile, null|string $alias = null): Import
    {
        return $this->addImport($coreFile->getFullyQualifiedNamespace(false), $coreFile->getEntityName(), $alias);
    }

    public function addCoreFileImports(CoreFile ...$coreFiles): self
    {
        foreach ($coreFiles as $cf) {
            $this->addCoreFileImport($cf);
        }
        return $this;
    }

    public function addCoreFileImportsByName(string ...$entityNames): self
    {
        foreach ($entityNames as $en) {
            $this->addCoreFileImport(
                $this->_config->getCoreFiles()->getCoreFileByEntityName($en),
            );
        }
        return $this;
    }

    public function addVersionCoreFileImportsByName(Version $version, string ...$entityNames): self
    {
        foreach ($entityNames as $en) {
            $coreFile = $version->getVersionCoreFiles()->getCoreFileByEntityName($en);
            $this->addImport($coreFile->getNamespace(), $coreFile->getEntityName());
        }
        return $this;
    }

    public function addVersionTypeImports(Type ...$types): self
    {
        foreach ($types as $type) {
            $this->addImport($type->getFullyQualifiedNamespace(false), $type->getClassName());
        }
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Builder\Import[]
     */
    public function getIterator(): iterable
    {
        $this->_sort();
        return new \ArrayIterator($this->_imports);
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
     * @return \DCarbone\PHPFHIR\Builder\Import
     */
    public function getImportByType(Type $type): Import
    {
        $fqn = $type->getFullyQualifiedClassName(false);
        foreach ($this->_imports as $import) {
            if ($import->getFullyQualifiedName(false) === $fqn) {
                return $import;
            }
        }
        throw ExceptionUtils::createMissingExpectedImportException($type->getFullyQualifiedClassName(false));
    }

    /**
     * @param string $classname
     * @param string $namespace
     * @return \DCarbone\PHPFHIR\Builder\Import|null
     */
    public function getImportByClassAndNamespace(string $classname, string $namespace): null|Import
    {
        $this->_sort();
        foreach ($this->_imports as $import) {
            if ($import->getNamespace() === $namespace && $import->getName() === $classname) {
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

    private function _sort(): void
    {
        if ($this->_sorted) {
            return;
        }
        uasort(
            $this->_imports,
            function (Import $a, Import $b) {
                return strnatcasecmp($a->getFullyQualifiedName(false), $b->getFullyQualifiedName(false));
            }
        );
        $this->_sorted = true;
    }
}