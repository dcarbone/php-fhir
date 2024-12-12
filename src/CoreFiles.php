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

class CoreFiles
{
    /** @var string */
    private string $_outputDir;
    /** @var string */
    private string $_templateDir;

    /** @var \DCarbone\PHPFHIR\CoreFile[] */
    private array $_files;

    /**
     * @param string $outputDir
     * @param string $templateDir
     * @param string $baseNS
     * @param string $testNS
     */
    public function __construct(string $outputDir, string $templateDir, string $baseNS, string $testNS)
    {
        $this->_outputDir = $outputDir;
        $this->_templateDir = $templateDir;

        foreach ($this->getTemplateFileIterator() as $fpath => $fi) {
            /** @var $fi \SplFileInfo */
            var_dump($fpath);
            $this->_files[] = new CoreFile($fpath, $outputDir, $baseNS, $testNS);
        }
    }

    /**
     * @return string
     */
    public function getOutputDir(): string
    {
        return $this->_outputDir;
    }

    /**
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->_templateDir;
    }

    /**
     * @return \RecursiveIteratorIterator
     */
    public function getTemplateFileIterator(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->_templateDir,
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::SKIP_DOTS,
            ),
        );
    }

    /**
     * @return \DCarbone\PHPFHIR\CoreFile[]
     */
    public function getIterator(): \SplFixedArray
    {
        $files = $this->_files;
        usort($files, function(CoreFile $a, CoreFile $b): int {
            return strnatcasecmp($a->getClassname(), $b->getClassname());
        });
        return \SplFixedArray::fromArray($files, false);
    }

    public function getAutoloadersIterator(): \SplFixedArray
    {
        $autoloaders = [];
        foreach ($this->_files as $file) {
            if ($file->isAutoloader()) {
                $autoloaders[] = $file;
            }
        }
        usort($autoloaders, function(CoreFile $a, CoreFile $b): int {
            return strnatcasecmp($a->getClassname(), $b->getClassname());
        });
        return \SplFixedArray::fromArray($autoloaders, false);
    }
}