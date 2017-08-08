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
    private $outputPath = PHPFHIR_DEFAULT_OUTPUT_DIR;
    /** @var string */
    private $outputNamespace = PHPFHIR_DEFAULT_NAMESPACE;

    /** @var array */
    private $xmlSerializationAttributeOverrides = [];

    /**
     * Config constructor.
     * @param array $conf
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $conf = [], LoggerInterface $logger = null) {
        if ($logger) {
            $this->logger = new Logger($logger);
        } else {
            $this->logger = new Logger(new NullLogger());
        }

        foreach ($conf as $k => $v) {
            $this->{'set' . ucfirst($k)}($v);
        }

        // be lazy...
        $this->setXsdPath(isset($this->xsdPath) ? $this->xsdPath : null);
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getXsdPath() {
        return $this->xsdPath;
    }

    /**
     * @param string $xsdPath
     * @return \DCarbone\PHPFHIR\ClassGenerator\Config
     */
    public function setXsdPath($xsdPath) {
        // Bunch'o validation
        if (false === is_dir($xsdPath)) {
            throw new \RuntimeException('Unable to locate XSD dir "' . $xsdPath . '"');
        }
        if (false === is_readable($xsdPath)) {
            throw new \RuntimeException('This process does not have read access to directory "' . $xsdPath . '"');
        }
        $this->xsdPath = rtrim($xsdPath, "/\\");
        return $this;
    }

    /**
     * @return string
     */
    public function getOutputPath() {
        return $this->outputPath;
    }

    /**
     * @param string $outputPath
     * @return \DCarbone\PHPFHIR\ClassGenerator\Config
     */
    public function setOutputPath($outputPath) {
        if (!is_dir($outputPath)) {
            throw new \RuntimeException('Unable to locate output dir "' . $outputPath . '"');
        }
        if (!is_writable($outputPath)) {
            throw new \RuntimeException(sprintf('Specified output path "%s" is not writable by this process.', $outputPath));
        }
        if (!is_readable($outputPath)) {
            throw new \RuntimeException(sprintf('Specified output path "%s" is not readable by this process.', $outputPath));
        }
        $this->outputPath = $outputPath;
        return $this;
    }

    /**
     * @return array
     */
    public function getXmlSerializationAttributeOverrides() {
        return $this->xmlSerializationAttributeOverrides;
    }

    /**
     * @param string $elementName
     * @param string $attributeName
     * @return bool
     */
    public function getXmlSerializationAttributeOverride($elementName, $attributeName) {
        return isset($this->xmlSerializationAttributeOverrides[$elementName]) && $this->xmlSerializationAttributeOverrides[$elementName] === $attributeName;
    }

    /**
     * @param string $elementName
     * @param string $propertyName
     * @return \DCarbone\PHPFHIR\ClassGenerator\Config
     */
    public function setXmlSerializationAttributeOverride($elementName, $propertyName) {
        $this->xmlSerializationAttributeOverrides[$elementName] = $propertyName;
        return $this;
    }

    /**
     * @param array $xmlSerializationAttributeOverrides
     * @return \DCarbone\PHPFHIR\ClassGenerator\Config
     */
    public function setXmlSerializationAttributeOverrides(array $xmlSerializationAttributeOverrides) {
        $this->xmlSerializationAttributeOverrides = [];
        foreach($xmlSerializationAttributeOverrides as $k => $v) {
            $this->setXmlSerializationAttributeOverride($k, $v);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getOutputNamespace() {
        return $this->outputNamespace;
    }

    /**
     * @param string $outputNamespace
     * @return \DCarbone\PHPFHIR\ClassGenerator\Config
     */
    public function setOutputNamespace($outputNamespace) {
        if (null === $outputNamespace) {
            $outputNamespace = PHPFHIR_DEFAULT_NAMESPACE;
        }
        if (false === NameUtils::isValidNSName($outputNamespace)) {
            throw new \InvalidArgumentException(sprintf('Specified root namespace "%s" is not a valid PHP namespace.', $outputNamespace));
        }
        $this->outputNamespace = trim($outputNamespace, "\\;");
        return $this;
    }
}