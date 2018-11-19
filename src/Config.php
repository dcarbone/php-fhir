<?php namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Config
 * @package DCarbone\PHPFHIR
 */
class Config implements LoggerAwareInterface
{
    const KEY_XSD_PATH         = 'xsdPath';
    const KEY_OUTPUT_PATH      = 'outputPath';
    const KEY_OUTPUT_NAMESPACE = 'outputNamespace';
    const KEY_MUNGE            = 'munge';
    const KEY_GENERATE_TESTS   = 'generateTests';

    /** @var \DCarbone\PHPFHIR\Logger */
    private $logger;

    /** @var string */
    private $xsdPath;
    /** @var string */
    private $outputPath = PHPFHIR_DEFAULT_OUTPUT_DIR;
    /** @var string */
    private $outputNamespace = PHPFHIR_DEFAULT_NAMESPACE;

    /** @var bool */
    private $generateTests = false;
    /** @var bool */
    private $munge = false;

    /**
     * Config constructor.
     * @param array $conf
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $conf = [], LoggerInterface $logger = null)
    {
        if ($logger) {
            $this->logger = new Logger($logger);
        } else {
            $this->logger = new Logger(new NullLogger());
        }

        foreach ($conf as $k => $v) {
            $this->{'set' . ucfirst($k)}($v);
        }

        // be lazy...
        $this->setXSDPath(isset($this->xsdPath) ? $this->xsdPath : null);
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = new Logger($logger);
    }

    /**
     * @return string
     */
    public function getXsdPath()
    {
        return $this->xsdPath;
    }

    /**
     * @param string $xsdPath
     * @return $this
     */
    public function setXSDPath($xsdPath)
    {
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
    public function getOutputPath()
    {
        return $this->outputPath;
    }

    /**
     * @param string $outputPath
     * @return $this
     */
    public function setOutputPath($outputPath)
    {
        if (!is_dir($outputPath)) {
            throw new \RuntimeException('Unable to locate output dir "' . $outputPath . '"');
        }
        if (!is_writable($outputPath)) {
            throw new \RuntimeException(sprintf('Specified output path "%s" is not writable by this process.',
                $outputPath));
        }
        if (!is_readable($outputPath)) {
            throw new \RuntimeException(sprintf('Specified output path "%s" is not readable by this process.',
                $outputPath));
        }
        $this->outputPath = $outputPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getOutputNamespace()
    {
        return $this->outputNamespace;
    }

    /**
     * @param string $outputNamespace
     * @return $this
     */
    public function setOutputNamespace($outputNamespace)
    {
        if (null === $outputNamespace) {
            $outputNamespace = PHPFHIR_DEFAULT_NAMESPACE;
        }
        $outputNamespace = ltrim($outputNamespace, "\\");
        if (false === NameUtils::isValidNSName($outputNamespace)) {
            throw new \InvalidArgumentException(sprintf('Specified root namespace "%s" is not a valid PHP namespace.',
                $outputNamespace));
        }
        $this->outputNamespace = trim($outputNamespace, "\\;");
        return $this;
    }

    /**
     * @param bool $generateTests
     * @return $this
     */
    public function setGenerateTests($generateTests)
    {
        $this->generateTests = (bool)$generateTests;
        return $this;
    }

    /**
     * @return bool
     */
    public function mustGenerateTests()
    {
        return $this->generateTests;
    }

    /**
     * @param bool $munge
     * @return $this
     */
    public function setMunge($munge)
    {
        $this->munge = (bool)$munge;
        return $this;
    }

    /**
     * @return bool
     */
    public function mustMunge()
    {
        return $this->munge;
    }
}