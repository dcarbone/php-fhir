<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    private $classname;
    /** @var string */
    private $namespace;
    /** @var string */
    private $fqcn;
    /** @var bool */
    private $aliased;
    /** @var string */
    private $aliasName;
    /** @var bool */
    private $requiresImport;

    /**
     * TypeImport constructor.
     * @param string $classname
     * @param string $namespace
     * @param bool $aliased
     * @param string $aliasName
     * @param bool $requiresImport
     */
    public function __construct($classname, $namespace, $aliased, $aliasName, $requiresImport)
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
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return bool
     */
    public function isAliased()
    {
        return $this->aliased;
    }

    /**
     * @return string
     */
    public function getAliasName()
    {
        return $this->aliasName;
    }

    /**
     * @return string
     */
    public function getImportedName()
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
    public function getFullyQualifiedNamespace($leadingSlash)
    {
        return $leadingSlash ? "\\{$this->namespace}" : $this->namespace;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassname($leadingSlash)
    {
        return $leadingSlash ? "\\{$this->fqcn}" : $this->fqcn;
    }

    /**
     * @return bool
     */
    public function isRequiresImport()
    {
        return $this->requiresImport;
    }

    /**
     * @return string
     */
    public function getUseStatement()
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