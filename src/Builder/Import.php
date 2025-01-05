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

class Import
{
    /** @var string */
    private string $_classname;
    /** @var string */
    private string $_namespace;
    /** @var string */
    private string $_fqn;
    /** @var string */
    private string $_aliasName;
    /** @var bool */
    private bool $_requiresImport;

    /**
     * TypeImport constructor.
     * @param string $classname
     * @param string $namespace
     * @param string $aliasName
     * @param bool $requiresImport
     */
    public function __construct(string $classname, string $namespace, string $aliasName, bool $requiresImport)
    {
        $this->_classname = $classname;
        $this->_namespace = trim($namespace, PHPFHIR_NAMESPACE_SEPARATOR);
        $this->_fqn = ('' === $this->_namespace ? $classname : "{$this->_namespace}\\{$classname}");
        $this->_aliasName = $aliasName;
        $this->_requiresImport = $requiresImport;
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->_classname;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    /**
     * @return bool
     */
    public function isAliased(): bool
    {
        return isset($this->_aliasName) && '' !== $this->_aliasName;
    }

    /**
     * @return string
     */
    public function getAliasName(): string
    {
        return $this->_aliasName;
    }

    /**
     * @return string
     */
    public function getImportedName(): string
    {
        if ($this->isAliased()) {
            return $this->getAliasName();
        }
        return $this->getClassname();
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace(bool $leadingSlash): string
    {
        return $leadingSlash ? "\\{$this->_namespace}" : $this->_namespace;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash): string
    {
        return $leadingSlash ? "\\{$this->_fqn}" : $this->_fqn;
    }

    /**
     * @return bool
     */
    public function requiresImport(): bool
    {
        return $this->_requiresImport;
    }

    /**
     * @return string
     */
    public function getUseStatement(): string
    {
        $use = "use {$this->getFullyQualifiedName(false)}";
        if ($this->isAliased()) {
            $use .= " as {$this->getAliasName()}";
        }
        return $use . ";\n";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getImportedName();
    }
}