<?php namespace DCarbone\PHPFHIR\ClassGenerator;

/*
 * Copyright 2016-2017 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR\ParserMapTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR\ResponseParserTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\FileUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;
use DCarbone\PHPFHIR\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Generator
 * @package PHPFHIR
 */
class Generator implements LoggerAwareInterface
{
    /** @var Logger */
    protected $logger;

    /** @var string */
    protected $xsdPath;
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

    /**
     * Constructor
     *
     * @param string $xsdPath
     * @param null|string $outputPath
     * @param string $outputNamespace
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($xsdPath, $outputPath = null, $outputNamespace = null, LoggerInterface $logger = null)
    {
        if (null === $logger)
            $this->logger = new Logger(new NullLogger());
        else
            $this->logger = new Logger($logger);

        $this->logger->info('Validating Generator input...');

        // Validate our input, will throw exception if bad.
        list(
            $this->xsdPath,
            $this->outputPath,
            $this->outputNamespace) = self::_validateInput($xsdPath, $outputPath, $outputNamespace);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new Logger($logger);
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

            FileUtils::createDirsFromNS($this->outputPath, $classTemplate->getNamespace(), $this->logger);

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
        // Class props
        $this->logger->startBreak('XSD Parsing');
        $this->XSDMap = XSDMapGenerator::buildXSDMap($this->xsdPath, $this->outputNamespace, $this->logger);
        $this->logger->endBreak('XSD Parsing');

        // Initialize some classes and things.
        self::_initializeClasses($this->xsdPath, $this->outputPath, $this->outputNamespace, $this->logger);

        $this->_mapTemplate = new ParserMapTemplate($this->outputPath, $this->outputNamespace);
        $this->_autoloadMap = new AutoloaderTemplate($this->outputPath, $this->outputNamespace);

        $this->_autoloadMap->addEntry(
            $this->_mapTemplate->getClassName(),
            $this->_mapTemplate->getClassPath()
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

        return [rtrim($xsdPath, "/\\"), $outputPath, trim($outputNamespace, "\\;")];
    }

    /**
     * @param string $xsdPath
     * @param string $outputPath
     * @param string $outputNamespace
     * @param Logger $logger
     */
    private static function _initializeClasses($xsdPath, $outputPath, $outputNamespace, Logger $logger)
    {
        $logger->info('Compiling Copyrights...');
        CopyrightUtils::compileCopyrights($xsdPath, $logger);

        $logger->info('Initializing ClassGenerator...');
        ClassGenerator::init($outputNamespace, $logger);

        $logger->info('Creating root directory...');
        FileUtils::createDirsFromNS($outputPath, $outputNamespace, $logger);
    }
}
