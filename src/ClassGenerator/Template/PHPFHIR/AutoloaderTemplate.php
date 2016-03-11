<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;

/**
 * Class AutoloaderTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR
 */
class AutoloaderTemplate extends AbstractPHPFHIRClassTemplate
{
    /** @var array */
    private $_classMap = array();

    /**
     * Constructor
     *
     * @param string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($outputPath, $outputNamespace)
    {
        parent::__construct($outputPath, $outputNamespace, 'PHPFHIRAutoloader');
    }

    /**
     * @param string $fullClassName
     * @param string $classFilePath
     */
    public function addEntry($fullClassName, $classFilePath)
    {
        $this->_classMap[$fullClassName] = ltrim(
            str_replace(
                array($this->outputPath, $this->outputNamespace, '\\'),
                array('', '', '/'),
                $classFilePath
            ),
            "/\\"
        );
    }

    /**
     * @param ClassTemplate $classTemplate
     */
    public function addPHPFHIRClassEntry(ClassTemplate $classTemplate)
    {
        $this->addEntry(
            $classTemplate->compileFullyQualifiedClassName(false),
            $classTemplate->compileFullOutputPath($this->outputPath)
        );
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return sprintf(
            include PHPFHIR_TEMPLATE_DIR.'/autoload_template.php',
            $this->outputNamespace,
            CopyrightUtils::getBasePHPFHIRCopyrightComment(),
            var_export($this->_classMap, true)
        );
    }

    /**
     * @return bool
     */
    public function writeToFile()
    {
        return (bool)file_put_contents(
            $this->classPath,
            $this->compileTemplate()
        );
    }
}