<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\NameUtils;

class CoreFile
{
    /** @var string */
    private string $_templateFile;
    /** @var string */
    private string $_type;
    /** @var string */
    private string $_filepath;
    /** @var string */
    private string $_filename;
    /** @var string */
    private string $_namespace;
    /** @var string */
    private string $_entityName;
    /** @var bool */
    private bool $_isAbstract = false;
    /** @var bool */
    private bool $_isTest = false;
    /** @var bool */
    private bool $_isAutoloader = false;

    /**
     * @param string $templateFile
     * @param string $outDir
     * @param string $baseNS
     */
    public function __construct(string $templateFile, string $outDir, string $baseNS)
    {
        $this->_templateFile = $templateFile;

        // get filename
        $this->_filename = basename($templateFile);
        // store "type"
        $this->_type = substr($this->_filename, 0, strpos($this->_filename, '_'));
        // trim "type" and ".php"
        $this->_filename = strstr(substr($this->_filename, strlen($this->_type) + 1), '.', true);
        // classname suffix
        $suffix = NameUtils::phpNameFormat($this->_type);

        $this->_namespace = ltrim($baseNS, NAMESPACE_SEPARATOR);

        if ('class' === $this->_type) {
            // 'class' types do have suffix
            $suffix = '';
            $this->_isAutoloader = str_starts_with($this->_filename, 'autoloader');
            $this->_isAbstract = str_starts_with($this->_filename, 'abstract');
        } else if ('test' === $this->_type) {
            // mark this as a test file
            $this->_isTest = true;
            // trim subtype
            $this->_filename = substr($this->_filename, 6);
        }

        // construct class filename
        $this->_entityName = NameUtils::templateFilenameToPHPName($this->_filename) . $suffix;

        // build full filepath
        $this->_filepath = $outDir . DIRECTORY_SEPARATOR . "{$this->_entityName}.php";
    }

    /**
     * @return string
     */
    public function getTemplateFile(): string
    {
        return $this->_templateFile;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * @return string
     */
    public function getFilepath(): string
    {
        return $this->_filepath;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->_filename;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->_entityName;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace(bool $leadingSlash): string
    {
        return match ($leadingSlash) {
            true => NAMESPACE_SEPARATOR . $this->_namespace,
            default => $this->_namespace,
        };
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash): string
    {
        return $this->getFullyQualifiedNamespace($leadingSlash) . NAMESPACE_SEPARATOR . $this->_entityName;
    }

    /**
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->_isTest;
    }

    /**
     * @return bool
     */
    public function isAutoloader(): bool
    {
        return $this->_isAutoloader;
    }

    /**
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->_isAbstract;
    }
}