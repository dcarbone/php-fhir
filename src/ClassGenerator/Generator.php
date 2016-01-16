<?php namespace PHPFHIR\ClassGenerator;

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

use PHPFHIR\ClassGenerator\Generator\ClassGenerator;
use PHPFHIR\ClassGenerator\Template\AutoloaderTemplate;
use PHPFHIR\ClassGenerator\Template\ParserMapTemplate;
use PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;
use PHPFHIR\ClassGenerator\Utilities\FileUtils;
use PHPFHIR\ClassGenerator\Utilities\NameUtils;
use PHPFHIR\ClassGenerator\Utilities\XMLUtils;

/**
 * Class Generator
 * @package PHPFHIR
 */
class Generator
{
    /** @var string */
    protected $outputPath;
    /** @var string */
    protected $outputNamespace;
    /** @var XSDMap */
    protected $XSDMap;

    /** @var array */
    private $_autoloadMap = array();

    /**
     * Constructor
     *
     * @param string $xsdPath
     * @param string $outputNamespace
     * @param null|string $outputPath
     */
    public function __construct($xsdPath, $outputNamespace = 'PHPFHIRGenerated', $outputPath = null)
    {
        if (false === is_dir($xsdPath))
            throw new \RuntimeException('Unable to locate XSD dir "'.$xsdPath.'"');

        if (false === is_readable($xsdPath))
            throw new \RuntimeException('This process does not have read access to directory "'.$xsdPath.'"');

        if (false === NameUtils::isValidNSName($outputNamespace))
            throw new \InvalidArgumentException('Invalid namespace "'.$outputNamespace.'" specified.');

        $this->xsdPath = rtrim($xsdPath, "/\\");

        if (null === $outputPath)
            $outputPath = realpath(sprintf('%s/../../output', __DIR__));

        if (!is_dir($outputPath))
            throw new \RuntimeException('Unable to locate output dir "'.$outputPath.'"');

        $this->outputNamespace = trim($outputNamespace, "\\;");
        $this->outputPath = $outputPath;
        $this->XSDMap = XMLUtils::buildXSDMap($this->xsdPath, $this->outputNamespace);

        CopyrightUtils::compileCopyrights($this->xsdPath);
    }

    /**
     * Generate FHIR object classes based on XSD
     */
    public function generate()
    {
        $mapTemplate = new ParserMapTemplate($this->outputPath, $this->outputNamespace);

        foreach($this->XSDMap as $objectName=>$data)
        {
            $classTemplate = ClassGenerator::buildClassTemplate($this->XSDMap, $data);

            FileUtils::createDirsFromNS($this->outputPath, $classTemplate->getNamespace());

            // Generate class file
            $classTemplate->writeToFile($this->outputPath);

            // Add entry to autoload map
            $this->_autoloadMap[rtrim($classTemplate->getUseStatement(), ";")] = $classTemplate->compileFullOutputPath($this->outputPath);

            $mapTemplate->addElementClass($objectName, $classTemplate);
        }

        $autoloaderTemplate = new AutoloaderTemplate($this->outputPath, $this->outputNamespace, $this->_autoloadMap);
        $autoloaderTemplate->writeToFile();

        $mapTemplate->writeToFile();
    }
}