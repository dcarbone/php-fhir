<?php

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Config\Version;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Config
 * @package DCarbone\PHPFHIR
 */
class Config implements LoggerAwareInterface
{
    const KEY_SCHEMA_PATH  = 'schemaPath';
    const KEY_CLASSES_PATH = 'classesPath';
    const KEY_VERSIONS     = 'versions';
    const KEY_SILENT       = 'silent';

    /** @var string */
    private $schemaPath;

    /** @var string */
    private $classesPath = PHPFHIR_DEFAULT_OUTPUT_DIR;
    /** @var \DCarbone\PHPFHIR\Config\Version[] */
    private $versions = [];

    /** @var \DCarbone\PHPFHIR\Logger */
    private $log;
    /** @var bool */
    private $silent = false;

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
        if (!isset($conf[self::KEY_VERSIONS]) || !is_array($conf[self::KEY_VERSIONS]) || 0 == count($conf[self::KEY_VERSIONS])) {
            throw new \DomainException('Configuration key "' . self::KEY_VERSIONS . '" must be an array with at least 1 configured version.');
        }
        $this->setSchemaPath($conf[self::KEY_SCHEMA_PATH]);
        $this->setClassesPath($conf[self::KEY_CLASSES_PATH]);
        $this->setVersions($conf[self::KEY_VERSIONS]);
        $this->setSilent(isset($conf[self::KEY_SILENT]) ? (bool)$conf[self::KEY_SILENT] : false);
        if ($logger && !$this->isSilent()) {
            $this->log = new Logger($logger);
        } else {
            $this->log = new Logger(new NullLogger());
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
     * @return Config
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger()
    {
        return $this->log;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->log = new Logger($logger);
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
            throw new \RuntimeException(sprintf('Specified output path "%s" is not writable by this process.',
                $classesPath));
        }
        if (!is_readable($classesPath)) {
            throw new \RuntimeException(sprintf('Specified output path "%s" is not readable by this process.',
                $classesPath));
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
                'No version with name "' . $version . '" has been configured.  Available: ["' . implode('", "',
                    array_keys($this->versions)) . '"]'
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