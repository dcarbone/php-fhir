<?php

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractConfig implements LoggerAwareInterface
{
    public const KEY_SCHEMA_PATH = 'schemaPath';
    public const KEY_CLASSES_PATH = 'classesPath';
    public const KEY_VERSIONS = 'versions';
    public const KEY_SILENT = 'silent';
    public const KEY_SKIP_TESTS = 'skipTests';
    public const KEY_LIBXML_OPTS = 'libxmlOpts';

    /** @var \DCarbone\PHPFHIR\Logger */
    protected Logger $_log;

    /** @var string */
    private string $schemaPath;

    /** @var string */
    private string $classesPath = PHPFHIR_DEFAULT_OUTPUT_DIR;
    /** @var \DCarbone\PHPFHIR\Config\Version[] */
    private array $versions = [];

    /** @var bool */
    private bool $silent = false;
    /** @var bool */
    private bool $skipTests = false;
    /** @var int|null */
    private ?int $libxmlOpts;

    /**
     * Config constructor.
     * @param array $conf
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $conf = [], LoggerInterface $logger = null)
    {
        if (!isset($conf[self::KEY_SCHEMA_PATH])) {
            throw new \DomainException('Required configuration key "' . self::KEY_SCHEMA_PATH . '" missing');
        }
        if (!isset($conf[self::KEY_CLASSES_PATH])) {
            throw new \DomainException('Required configuration key "' . self::KEY_CLASSES_PATH . '" missing');
        }
        if (!isset($conf[self::KEY_VERSIONS]) || !is_array($conf[self::KEY_VERSIONS]) || 0 == count(
                $conf[self::KEY_VERSIONS]
            )) {
            throw new \DomainException(
                'Configuration key "' . self::KEY_VERSIONS . '" must be an array with at least 1 configured version.'
            );
        }
        $this->setSchemaPath($conf[self::KEY_SCHEMA_PATH]);
        $this->setClassesPath($conf[self::KEY_CLASSES_PATH]);
        $this->setVersions($conf[self::KEY_VERSIONS]);
        $this->setSilent(isset($conf[self::KEY_SILENT]) ? (bool)$conf[self::KEY_SILENT] : false);
        $this->setSkipTests(isset($conf[self::KEY_SKIP_TESTS]) ? $conf[self::KEY_SKIP_TESTS] : false);
        $this->setLibxmlOpts(isset($conf[self::KEY_LIBXML_OPTS]) ? $conf[self::KEY_LIBXML_OPTS] : null);
        if ($logger && !$this->isSilent()) {
            $this->_log = new Logger($logger);
        } else {
            $this->_log = new Logger(new NullLogger());
        }
    }

    /**
     * @return bool
     */
    public function isSilent()
    {
        return $this->silent;
    }

    /**
     * @param bool $silent
     * @return static
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSkipTests()
    {
        return $this->skipTests;
    }

    /**
     * @param bool $skipTests
     * @return static
     */
    public function setSkipTests($skipTests)
    {
        $this->skipTests = (bool)$skipTests;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLibxmlOpts()
    {
        return $this->libxmlOpts;
    }

    /**
     * @param int|null $libxmlOpts
     * @return static
     */
    public function setLibxmlOpts($libxmlOpts)
    {
        $this->libxmlOpts = $libxmlOpts;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger()
    {
        return $this->_log;
    }

    /**
     * @return string
     */
    public function getSchemaPath()
    {
        return $this->schemaPath;
    }

    /**
     * @param string $schemaPath
     * @return $this
     */
    public function setSchemaPath($schemaPath)
    {
        // Bunch'o validation
        if (false === is_dir($schemaPath)) {
            throw new \RuntimeException('Unable to locate XSD dir "' . $schemaPath . '"');
        }
        if (false === is_readable($schemaPath)) {
            throw new \RuntimeException('This process does not have read access to directory "' . $schemaPath . '"');
        }
        $this->schemaPath = rtrim($schemaPath, "/\\");
        return $this;
    }

    /**
     * @return string
     */
    public function getClassesPath()
    {
        return $this->classesPath;
    }

    /**
     * @param string $classesPath
     * @return $this
     */
    public function setClassesPath($classesPath)
    {
        if (!is_dir($classesPath)) {
            throw new \RuntimeException('Unable to locate output dir "' . $classesPath . '"');
        }
        if (!is_writable($classesPath)) {
            throw new \RuntimeException(
                sprintf(
                    'Specified output path "%s" is not writable by this process.',
                    $classesPath
                )
            );
        }
        if (!is_readable($classesPath)) {
            throw new \RuntimeException(
                sprintf(
                    'Specified output path "%s" is not readable by this process.',
                    $classesPath
                )
            );
        }
        $this->classesPath = $classesPath;
        return $this;
    }

    /**
     * @param array $versions
     * @return $this
     */
    public function setVersions(array $versions)
    {
        $this->versions = [];
        foreach ($versions as $name => $version) {
            $this->versions[$name] = ($version instanceof Version) ? $version : new Version($name, $version);
        }
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\Version[]
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function hasVersion($version)
    {
        return isset($this->versions[$version]);
    }

    /**
     * @param string $version
     * @return \DCarbone\PHPFHIR\Config\Version
     */
    public function getVersion($version)
    {
        if (!$this->hasVersion($version)) {
            throw new \OutOfBoundsException(
                'No version with name "' . $version . '" has been configured.  Available: ["' . implode(
                    '", "',
                    array_keys($this->versions)
                ) . '"]'
            );
        }
        return $this->versions[$version];
    }

    /**
     * @return array
     */
    public function listVersions()
    {
        return array_keys($this->versions);
    }
}