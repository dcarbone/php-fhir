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

use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;
use DCarbone\PHPFHIR\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Config
 * @package DCarbone\PHPFHIR\ClassGenerator
 */
class Config implements LoggerAwareInterface {

    use LoggerAwareTrait;

    /** @var string */
    private $xsdPath;
    /** @var string */
    private $outputPath;
    /** @var string */
    private $outputNamespace;
    /**
     * Config constructor.
     * @param string $xsdPath
     * @param string|null $outputPath
     * @param string|null $outputNamespace
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct($xsdPath, $outputPath = null, $outputNamespace = null, LoggerInterface $logger = null) {
        if ($logger) {
            $this->logger = new Logger($logger);
        } else {
            $this->logger = new Logger(new NullLogger());
        }

        $this->logger->info('Validating Generator input...');

        // Validate our input, will throw exception if bad.
        list(
            $this->xsdPath,
            $this->outputPath,
            $this->outputNamespace) = self::_validateInput($xsdPath, $outputPath, $outputNamespace);
    }

    /**
     * @return string
     */
    public function getXsdPath() {
        return $this->xsdPath;
    }

    /**
     * @return string
     */
    public function getOutputPath() {
        return $this->outputPath;
    }

    /**
     * @return string
     */
    public function getOutputNamespace() {
        return $this->outputNamespace;
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger() {
        return $this->logger;
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
}