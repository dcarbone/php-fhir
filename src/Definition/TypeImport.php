<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    private $aliased = false;
    /** @var string */
    private $aliasName;

    /**
     * TypeImport constructor.
     * @param string $classname
     * @param string $namespace
     * @param bool $aliased
     * @param string $aliasName
     */
    public function __construct($classname, $namespace, $aliased, $aliasName)
    {
        $this->classname = $classname;
        $this->namespace = $namespace;
        $this->fqcn = ('' === $namespace ? $classname : "{$namespace}\\{$classname}");
        $this->aliased = $aliased;
        $this->aliasName = $aliasName;
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
}