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

use DCarbone\PHPFHIR\Utilities\FileUtils;
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
    private string $_classname;
    /** @var bool */
    private bool $_isTest = false;
    /** @var bool */
    private bool $_isAutoloader = false;

    /**
     * @param string $templateFile
     * @param string $outDir
     * @param string $baseNS
     * @param string $testNS
     */
    public function __construct(string $templateFile, string $outDir, string $baseNS, string $testNS)
    {
        /** @var $fi \SplFileInfo */

        $this->_templateFile = $templateFile;

        // get filename
        $this->_filename = basename($templateFile);
        // store "type"
        $this->_type = substr($this->_filename, 0, strpos($this->_filename, '_'));
        // trim "type" and ".php"
        $this->_filename = strstr(substr($this->_filename, strpos($this->_filename, '_') + 1), '.', true);
        // classname suffix
        $suffix = ucfirst($this->_type);

        // define "default" namespace
        $this->_namespace = ltrim($baseNS, '\\');

        if ('class' === $this->_type) {
            // 'class' types do have suffix
            $suffix = '';
            $this->_isAutoloader = str_starts_with($this->_filename, 'autoloader');
        } else if ('test' === $this->_type) {
            // mark this as a test file
            $this->_isTest = true;
            // test classes have different namespace
            $this->_namespace = $testNS;
            // trim subtype
            $this->_filename = substr($this->_filename, strpos($this->_filename, '_') + 1);
        }

        // construct class filename
        $this->_classname = sprintf(
            '%s%s',
            implode('', array_map('classFilenameFormat', explode('_', $this->_filename))),
            $suffix
        );

        // build full filepath
        $this->_filepath = FileUtils::buildCoreFilePath(
            $outDir,
            $this->_namespace,
            $this->_classname,
        );
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
    public function getClassname(): string
    {
        return $this->_classname;
    }

    public function getFullyQualifiedName(bool $leadingSlash): string
    {
        if ('' === $this->_namespace) {
            $ns = $this->_classname;
        } else {
            $ns = sprintf('%s\\%s', $this->_namespace, $this->_classname);
        }
        return $leadingSlash ? sprintf('\\%s', $ns) : $ns;
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
}