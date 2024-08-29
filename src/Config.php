<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Config\VersionConfig;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Config
 * @package DCarbone\PHPFHIR
 */
class Config
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
    private string $classesPath;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig[] */
    private array $versions = [];

    /** @var bool */
    private bool $silent = false;
    /** @var bool */
    private bool $skipTests = false;
    /** @var int|null */
    private null|int $libxmlOpts;

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
        $this->setSilent(isset($conf[self::KEY_SILENT]) && (bool)$conf[self::KEY_SILENT]);
        $this->setSkipTests($conf[self::KEY_SKIP_TESTS] ?? false);
        $this->setLibxmlOpts($conf[self::KEY_LIBXML_OPTS] ?? null);
        if ($logger && !$this->isSilent()) {
            $this->_log = new Logger($logger);
        } else {
            $this->_log = new Logger(new NullLogger());
        }
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->_log = new Logger($logger);
    }

    /**
     * @return bool
     */
    public function isSilent(): bool
    {
        return $this->silent;
    }

    /**
     * @param bool $silent
     * @return static
     */
    public function setSilent(bool $silent): self
    {
        $this->silent = $silent;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSkipTests(): bool
    {
        return $this->skipTests;
    }

    /**
     * @param bool $skipTests
     * @return static
     */
    public function setSkipTests(bool $skipTests): self
    {
        $this->skipTests = $skipTests;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLibxmlOpts(): null|int
    {
        return $this->libxmlOpts;
    }

    /**
     * @param int|null $libxmlOpts
     * @return static
     */
    public function setLibxmlOpts(?int $libxmlOpts): self
    {
        $this->libxmlOpts = $libxmlOpts;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger(): Logger
    {
        return $this->_log;
    }

    /**
     * @return string
     */
    public function getSchemaPath(): string
    {
        return $this->schemaPath;
    }

    /**
     * @param string $schemaPath
     * @return $this
     */
    public function setSchemaPath(string $schemaPath): self
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
    public function getClassesPath(): string
    {
        return $this->classesPath;
    }

    /**
     * @param string $classesPath
     * @return $this
     */
    public function setClassesPath(string $classesPath): self
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
    public function setVersions(array $versions): self
    {
        $this->versions = [];
        foreach ($versions as $name => $version) {
            $this->versions[$name] = ($version instanceof VersionConfig) ? $version : new VersionConfig($this, new Version($name, $version));
        }
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function hasVersion(string $version): bool
    {
        return isset($this->versions[$version]);
    }

    /**
     * @param string $version
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getVersion(string $version): VersionConfig
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
    public function listVersions(): array
    {
        return array_keys($this->versions);
    }
}
