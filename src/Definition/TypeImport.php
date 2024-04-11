<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition;

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

/**
 * Class TypeImport
 * @package DCarbone\PHPFHIR\Definition
 */
class TypeImport
{
    /** @var string */
    private string $classname;
    /** @var string */
    private string $namespace;
    /** @var string */
    private string $fqcn;
    /** @var bool */
    private bool $aliased;
    /** @var string */
    private string $aliasName;
    /** @var bool */
    private bool $requiresImport;

    /**
     * TypeImport constructor.
     * @param string $classname
     * @param string $namespace
     * @param bool $aliased
     * @param string $aliasName
     * @param bool $requiresImport
     */
    public function __construct(string $classname, string $namespace, bool $aliased, string $aliasName, bool $requiresImport)
    {
        $this->classname = $classname;
        $this->namespace = $namespace;
        $this->fqcn = ('' === $namespace ? $classname : "{$namespace}\\{$classname}");
        $this->aliased = $aliased;
        $this->aliasName = $aliasName;
        $this->requiresImport = $requiresImport;
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return bool
     */
    public function isAliased(): bool
    {
        return $this->aliased;
    }

    /**
     * @return string
     */
    public function getAliasName(): string
    {
        return $this->aliasName;
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
        return $leadingSlash ? "\\{$this->namespace}" : $this->namespace;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassname(bool $leadingSlash): string
    {
        return $leadingSlash ? "\\{$this->fqcn}" : $this->fqcn;
    }

    /**
     * @return bool
     */
    public function isRequiresImport(): bool
    {
        return $this->requiresImport;
    }

    /**
     * @return string
     */
    public function getUseStatement(): string
    {
        $use = "use {$this->getFullyQualifiedClassname(false)}";
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