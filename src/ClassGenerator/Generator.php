<?php namespace DCarbone\PHPFHIR\ClassGenerator;

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

use DCarbone\PHPFHIR\ClassGenerator\Generator\ClassGenerator;
use DCarbone\PHPFHIR\ClassGenerator\Generator\XSDMapGenerator;
use DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR\AutoloaderTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR\JsonSerializableInterfaceTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR\ParserMapTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR\ResponseParserTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\FileUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

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

    /** @var AutoloaderTemplate */
    private $_autoloadMap;
    /** @var ParserMapTemplate */
    private $_mapTemplate;

    /** @var JsonSerializableInterfaceTemplate */
    private $_jsonSerializableInterface;

    /**
     * Constructor
     *
     * @param string $xsdPath
     * @param null|string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($xsdPath, $outputPath = null, $outputNamespace = null)
    {
        // Validate our input, will throw exception if bad.
        list($xsdPath, $outputPath, $outputNamespace) = self::_validateInput($xsdPath, $outputPath, $outputNamespace);

        // Class props
        $this->xsdPath = rtrim($xsdPath, "/\\");
        $this->outputNamespace = trim($outputNamespace, "\\;");
        $this->outputPath = $outputPath;
        $this->XSDMap = XSDMapGenerator::buildXSDMap($this->xsdPath, $this->outputNamespace);

        // Initialize some classes and things.
        self::_initializeClasses($xsdPath, $outputPath, $outputNamespace);
    }

    /**
     * Generate FHIR object classes based on XSD
     */
    public function generate()
    {
        $this->beforeGeneration();

        foreach($this->XSDMap as $fhirElementName=>$mapEntry)
        {
            $classTemplate = ClassGenerator::buildFHIRElementClassTemplate($this->XSDMap, $mapEntry);

            FileUtils::createDirsFromNS($this->outputPath, $classTemplate->getNamespace());

            // Generate class file
            $classTemplate->writeToFile($this->outputPath);

            $this->_mapTemplate->addEntry($classTemplate);
            $this->_autoloadMap->addPHPFHIRClassEntry($classTemplate);
        }

        $this->afterGeneration();
    }

    /**
     * Commands to run prior to class generation
     */
    protected function beforeGeneration()
    {
        $this->_mapTemplate = new ParserMapTemplate($this->outputPath, $this->outputNamespace);
        $this->_autoloadMap = new AutoloaderTemplate($this->outputPath, $this->outputNamespace);

        $this->_autoloadMap->addEntry(
            $this->_mapTemplate->getClassName(),
            $this->_mapTemplate->getClassPath()
        );

        $this->_jsonSerializableInterface = new JsonSerializableInterfaceTemplate($this->outputPath, $this->outputNamespace);
        $this->_jsonSerializableInterface->writeToFile();
        $this->_autoloadMap->addEntry(
            $this->_jsonSerializableInterface->getClassName(),
            $this->_jsonSerializableInterface->getClassPath()
        );
    }

    /**
     * Commands to run after class generation
     */
    protected function afterGeneration()
    {
        $this->_mapTemplate->writeToFile();
        $this->_autoloadMap->writeToFile();

        $responseParserTemplate = new ResponseParserTemplate($this->outputPath, $this->outputNamespace);
        $this->_autoloadMap->addEntry(
            $responseParserTemplate->getClassName(),
            $responseParserTemplate->getClassPath()
        );
        $responseParserTemplate->writeToFile();
    }

    /**
     * @param string $xsdPath
     * @param string $outputPath
     * @param string $outputNamespace
     * @return array
     */
    private static function _validateInput($xsdPath, $outputPath, $outputNamespace)
    {
        // Bunch'o validation
        if (false === is_dir($xsdPath))
            throw new \RuntimeException('Unable to locate XSD dir "'.$xsdPath.'"');

        if (false === is_readable($xsdPath))
            throw new \RuntimeException('This process does not have read access to directory "'.$xsdPath.'"');

        if (null === $outputPath)
            $outputPath = PHPFHIR_DEFAULT_OUTPUT_DIR;

        if (!is_dir($outputPath))
            throw new \RuntimeException('Unable to locate output dir "'.$outputPath.'"');

        if (!is_writable($outputPath))
            throw new \RuntimeException(sprintf('Specified output path "%s" is not writable by this process.', $outputPath));

        if (!is_readable($outputPath))
            throw new \RuntimeException(sprintf('Specified output path "%s" is not readable by this process.', $outputPath));

        if (null === $outputNamespace)
            $outputNamespace = PHPFHIR_DEFAULT_NAMESPACE;

        if (false === NameUtils::isValidNSName($outputNamespace))
            throw new \InvalidArgumentException(sprintf('Specified root namespace "%s" is not a valid PHP namespace.', $outputNamespace));

        return array($xsdPath, $outputPath, $outputNamespace);
    }

    /**
     * @param string $xsdPath
     * @param string $outputPath
     * @param string $outputNamespace
     */
    private static function _initializeClasses($xsdPath, $outputPath, $outputNamespace)
    {
        // Initialize some of our static classes
        CopyrightUtils::compileCopyrights($xsdPath);
        ClassGenerator::init($outputNamespace);

        // Create root NS dir
        FileUtils::createDirsFromNS($outputPath, $outputNamespace);
    }
}
