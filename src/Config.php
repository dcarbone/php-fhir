<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Version\DefaultConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Config
 * @package DCarbone\PHPFHIR
 */
class Config implements LoggerAwareInterface
{
    private const _DEFAULT_LIBXML_OPTS = LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL;

    /** @var \DCarbone\PHPFHIR\Logger */
    private Logger $_log;

    /** @var string */
    private string $_outputPath;

    /** @var string */
    private string $_rootNamespace;

    /** @var \DCarbone\PHPFHIR\Version[] */
    private array $_versions = [];

    /** @var bool */
    private bool $_silent = false;
    /** @var null|int */
    private null|int $_libxmlOpts;

    /** @var string */
    private string $_standardDate;

    /** @var array */
    private array $_phpFHIRCopyright;
    /** @var string */
    private string $_basePHPFHIRCopyrightComment;

    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_coreFiles;

    /**
     * @param string $outputPath
     * @param string $rootNamespace
     * @param iterable $versions
     * @param int $libxmlOpts
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(string $outputPath,
                                string $rootNamespace,
                                iterable $versions,
                                int $libxmlOpts = self::_DEFAULT_LIBXML_OPTS,
                                null|LoggerInterface $logger = null)
    {
        $this->setOutputPath($outputPath);
        $this->setRootNamespace($rootNamespace);
        $this->setVersions($versions);
        $this->setLibxmlOpts($libxmlOpts);

        if (null !== $logger && !$this->isSilent()) {
            $this->setLogger(new Logger($logger));
        } else {
            $this->setLogger(new NullLogger());
        }

        $this->_standardDate = date('F jS, Y H:iO');

        $this->_phpFHIRCopyright = [
            'This class was generated with the PHPFHIR library (https://github.com/dcarbone/php-fhir) using',
            'class definitions from HL7 FHIR (https://www.hl7.org/fhir/)',
            '',
            sprintf('Class creation date: %s', $this->_standardDate),
            '',
            'PHPFHIR Copyright:',
            '',
            sprintf('Copyright 2016-%d Daniel Carbone (daniel.p.carbone@gmail.com)', date('Y')),
            '',
            'Licensed under the Apache License, Version 2.0 (the "License");',
            'you may not use this file except in compliance with the License.',
            'You may obtain a copy of the License at',
            '',
            '       http://www.apache.org/licenses/LICENSE-2.0',
            '',
            'Unless required by applicable law or agreed to in writing, software',
            'distributed under the License is distributed on an "AS IS" BASIS,',
            'WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.',
            'See the License for the specific language governing permissions and',
            'limitations under the License.',
            '',
        ];

        $this->_basePHPFHIRCopyrightComment = sprintf(
            "/*!\n * %s\n */",
            implode("\n * ", $this->_phpFHIRCopyright)
        );

        $this->_coreFiles = new CoreFiles(
            $this,
            $this->getOutputPath(),
            PHPFHIR_TEMPLATE_CORE_DIR,
            $this->getFullyQualifiedName(true),
        );
    }

    public static function fromArray(array $data): Config
    {
        if (!isset($data['outputPath'])) {
            throw new \InvalidArgumentException('Key "outputPath" is required.');
        }
        if (!isset($data['rootNamespace'])) {
            throw new \InvalidArgumentException('Key "rootNamespace" is required.');
        }
        if (isset($data['versions']) && !is_iterable($data['versions'])) {
            throw new \InvalidArgumentException('Key "versions" must be iterable.');
        }
        if (isset($data['logger']) && !($data['logger'] instanceof LoggerInterface)) {
            throw new \InvalidArgumentException('Key "logger" must be an instance of Psr\Log\LoggerInterface');
        }
        return new Config(
            outputPath: $data['outputPath'],
            rootNamespace: $data['rootNamespace'],
            versions: $data['versions'],
            libxmlOpts: $data['libxmlOpts'] ?? self::_DEFAULT_LIBXML_OPTS,
            logger: $data['logger'] ?? null,
        );
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        if ($logger instanceof Logger) {
            $this->_log = $logger;
        } else {
            $this->_log = new Logger($logger);
        }
    }

    /**
     * @return string
     */
    public function getRootNamespace(): string
    {
        return $this->_rootNamespace;
    }

    /**
     * @param string $rootNamespace
     * @return self
     */
    public function setRootNamespace(string $rootNamespace): self
    {
        $rootNamespace = trim($rootNamespace, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if ('' === $rootNamespace) {
            throw new \InvalidArgumentException('Root namespace must not be empty');
        }

        if (!NameUtils::isValidNSName($rootNamespace)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Root namespace "%s" is not a valid PHP namespace.',
                    $rootNamespace
                )
            );
        }

        $this->_rootNamespace = $rootNamespace;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSilent(): bool
    {
        return $this->_silent;
    }

    /**
     * @param bool $silent
     * @return static
     */
    public function setSilent(bool $silent): self
    {
        $this->_silent = $silent;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getLibxmlOpts(): null|int
    {
        return $this->_libxmlOpts;
    }

    /**
     * @param null|int $libxmlOpts
     * @return static
     */
    public function setLibxmlOpts(?int $libxmlOpts): self
    {
        $this->_libxmlOpts = $libxmlOpts;
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
    public function getOutputPath(): string
    {
        return $this->_outputPath;
    }

    /**
     * @param string $outputPath
     * @return $this
     */
    public function setOutputPath(string $outputPath): self
    {
        if (!is_dir($outputPath)) {
            throw new \RuntimeException('Unable to locate output dir "' . $outputPath . '"');
        }
        if (!is_writable($outputPath)) {
            throw new \RuntimeException(
                sprintf(
                    'Specified output path "%s" is not writable by this process.',
                    $outputPath
                )
            );
        }
        if (!is_readable($outputPath)) {
            throw new \RuntimeException(
                sprintf(
                    'Specified output path "%s" is not readable by this process.',
                    $outputPath
                )
            );
        }
        $this->_outputPath = $outputPath;
        return $this;
    }

    /**
     * @param array|\DCarbone\PHPFHIR\Version $version
     * @return self
     */
    public function addVersion(array|Version $version): self
    {
        if (is_array($version)) {
            if (!isset($version['name'])) {
                throw new \InvalidArgumentException('Version name is required');
            }
            if (!isset($version['namespace'])) {
                throw new \InvalidArgumentException('Version namespace is required');
            }
            if (!isset($version['schemaPath'])) {
                throw new \InvalidArgumentException('Path to schemas for version is required');
            }
            $defaultConfig = null;
            if (isset($version['defaultConfig'])) {
                if ($version['defaultConfig'] instanceof DefaultConfig) {
                    $defaultConfig = $version['defaultConfig'];
                } else if (is_array($version['defaultConfig'])) {
                    $defaultConfig = new Version\DefaultConfig($version['defaultConfig']);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        'key "defaultConfig" must either be instance of "%s", array, or null, %s seen.',
                        DefaultConfig::class,
                        gettype($version['defaultConfig'])
                    ));
                }
            }
            $version = new Version(
                config: $this,
                name: $version['name'],
                namespace: $version['namespace'],
                schemaPath: $version['schemaPath'],
                defaultConfig: $defaultConfig,
            );
        }
        if (isset($this->_versions[$version->getName()])) {
            throw new \InvalidArgumentException(sprintf('Version "%s" has already been added', $version->getName()));
        }
        $this->_versions[$version->getName()] = $version;
        return $this;
    }

    /**
     * @param iterable<array|\DCarbone\PHPFHIR\Version> $versions Iterable containing Versions or Version configuration maps.
     * @return self
     */
    public function setVersions(iterable $versions): self
    {
        $this->_versions = [];
        foreach ($versions as $version) {
            $this->addVersion($version);
        }
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version[]
     */
    public function getVersionsIterator(): iterable
    {
        foreach ($this->_versions as $v) {
            yield $v;
        }
    }

    /**
     * @param string $version
     * @return bool
     */
    public function hasVersion(string $version): bool
    {
        return isset($this->_versions[$version]);
    }

    /**
     * @param string $version
     * @return \DCarbone\PHPFHIR\Version
     */
    public function getVersion(string $version): Version
    {
        if (!$this->hasVersion($version)) {
            throw new \OutOfBoundsException(sprintf(
                    'No version with name "%s" has been configured.  Available: ["%s"]',
                    $version,
                    implode('", "', array_keys($this->_versions)),
                )
            );
        }
        return $this->_versions[$version];
    }

    /**
     * @return array
     */
    public function getVersionNames(): array
    {
        return array_keys($this->_versions);
    }

    /**
     * @return \DCarbone\PHPFHIR\CoreFiles
     */
    public function getCoreFiles(): CoreFiles
    {
        return $this->_coreFiles;
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string...$bits): string
    {
        $ns = $leadingSlash ? "\\$this->_rootNamespace" : $this->_rootNamespace;
        $bits = array_filter($bits);
        if ([] === $bits) {
            return $ns;
        }
        return sprintf('%s\\%s', $ns, implode('\\', $bits));
    }

    /**
     * @return string
     */
    public function getStandardDate(): string
    {
        return $this->_standardDate;
    }

    /**
     * @return array
     */
    public function getPHPFHIRCopyright(): array
    {
        return $this->_phpFHIRCopyright;
    }

    /**
     * @param bool $trailingNewline
     * @return string
     */
    public function getBasePHPFHIRCopyrightComment(bool $trailingNewline): string
    {
        return $this->_basePHPFHIRCopyrightComment . ($trailingNewline ? "\n" : '');
    }
}
