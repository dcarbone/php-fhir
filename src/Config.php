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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Utilities\FileUtils;
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
    private const _DEFAULT_LIBXML_OPTS = LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL;
    private const _DEFAULT_LIBRARY_NAMESPACE_PREFIX = 'DCarbone\\PHPFHIRGenerated';
    private const _DEFAULT_TESTS_NAMESPACE_PREFIX = 'Tests';

    /** @var \DCarbone\PHPFHIR\Logger */
    private Logger $_log;

    /** @var string */
    private string $_libraryPath;

    /** @var string */
    private string $_libraryNamespacePrefix;

    /** @var string */
    private string $_testsPath;

    /** @var string */
    private string $_testsNamespacePrefix;

    /** @var \DCarbone\PHPFHIR\Version[] */
    private array $_versions = [];

    /** @var string[] */
    private array $_versionNamespaces = [];

    /** @var bool */
    private bool $_silent = false;
    /** @var null|int */
    private null|int $_librarySchemaLibxmlOpts;

    /** @var string */
    private string $_standardDate;

    /** @var array */
    private array $_phpFHIRCopyright;
    /** @var string */
    private string $_basePHPFHIRCopyrightComment;

    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_coreFiles;

    /** @var \DCarbone\PHPFHIR\CoreFiles */
    private CoreFiles $_coreTestFiles;

    /**
     * @param string $libraryPath
     * @param array[]|\DCarbone\PHPFHIR\Config\VersionConfig[] $versions Array of VersionConfig maps or intsances.
     * @param string $libraryNamespacePrefix
     * @param null|string $testsPath
     * @param string $testNamespacePrefix
     * @param int $librarySchemaLibxmlOpts
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(string               $libraryPath,
                                iterable             $versions,
                                string               $libraryNamespacePrefix = self::_DEFAULT_LIBRARY_NAMESPACE_PREFIX,
                                int                  $librarySchemaLibxmlOpts = self::_DEFAULT_LIBXML_OPTS,
                                null|string          $testsPath = null,
                                string               $testNamespacePrefix = self::_DEFAULT_TESTS_NAMESPACE_PREFIX,
                                null|LoggerInterface $logger = null)
    {
        $this->setLibraryPath($libraryPath);
        $this->setLibraryNamespacePrefix($libraryNamespacePrefix);
        $this->setLibrarySchemaLibxmlOpts($librarySchemaLibxmlOpts);
        $this->setTestsPath($testsPath);
        $this->setTestsNamespacePrefix($testNamespacePrefix);

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
            $this->getLibraryPath(),
            PHPFHIR_TEMPLATE_CORE_DIR,
            $this->getFullyQualifiedName(true),
        );

        $this->setVersions($versions);
    }

    public static function fromArray(array $data): Config
    {
        if (!isset($data['libraryPath'])) {
            throw new \InvalidArgumentException('Key "libraryPath" is required.');
        }
        if (!isset($data['versions']) || !is_iterable($data['versions'])) {
            throw new \InvalidArgumentException('Key "versions" must be iterable.');
        }
        if (isset($data['logger']) && !($data['logger'] instanceof LoggerInterface)) {
            throw new \InvalidArgumentException(sprintf('Key "logger" must be undefined or be an instance of %s', LoggerInterface::class));
        }
        return new Config(
            libraryPath: $data['libraryPath'],
            versions: $data['versions'],
            libraryNamespacePrefix: $data['libraryNamespacePrefix'] ?? self::_DEFAULT_LIBRARY_NAMESPACE_PREFIX,
            librarySchemaLibxmlOpts: $data['librarySchemaLibxmlOpts'] ?? self::_DEFAULT_LIBXML_OPTS,
            testsPath: $data['testsPath'] ?? null,
            testNamespacePrefix: $data['testNamespacePrefix'] ?? self::_DEFAULT_TESTS_NAMESPACE_PREFIX,
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
     * @return \DCarbone\PHPFHIR\Logger
     */
    public function getLogger(): Logger
    {
        return $this->_log;
    }

    /**
     * @return string
     */
    public function getLibraryPath(): string
    {
        return $this->_libraryPath;
    }

    /**
     * @param string $libraryPath
     * @return self
     */
    public function setLibraryPath(string $libraryPath): self
    {
        FileUtils::assertDirReadableAndWriteable($libraryPath);
        $this->_libraryPath = $libraryPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getLibraryNamespacePrefix(): string
    {
        return $this->_libraryNamespacePrefix;
    }

    /**
     * @param string $libraryNamespacePrefix
     * @return self
     */
    public function setLibraryNamespacePrefix(string $libraryNamespacePrefix): self
    {
        $libraryNamespacePrefix = trim($libraryNamespacePrefix, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if ('' === $libraryNamespacePrefix) {
            throw new \InvalidArgumentException('Library namespace must not be empty');
        }

        if (!NameUtils::isValidNSName($libraryNamespacePrefix)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Library namespace prefix "%s" is not a valid PHP namespace.',
                    $libraryNamespacePrefix
                )
            );
        }

        $this->_libraryNamespacePrefix = $libraryNamespacePrefix;
        return $this;
    }

    /**
     * @param array|\DCarbone\PHPFHIR\Config\VersionConfig $versionConfig
     * @return self
     */
    public function addVersion(array|VersionConfig $versionConfig): self
    {
        if (is_array($versionConfig)) {
            $versionConfig = VersionConfig::fromArray($versionConfig);
        }

        $version = new Version(config: $this, versionConfig: $versionConfig);

        // ensure unique version name
        if (isset($this->_versions[$version->getName()])) {
            throw new \InvalidArgumentException(sprintf('Version "%s" has already been added.', $version->getName()));
        }

        // ensure unique version namespace
        $namespace = $version->getNamespace();
        if (false !== ($idx = array_search($namespace, $this->_versionNamespaces))) {
            throw new \DomainException(sprintf(
                'Version "%s" namespace "%s" conflicts with existing version "%s".',
                $version->getName(),
                $namespace,
                $idx,
            ));
        }

        $this->_versionNamespaces[$version->getName()] = $namespace;
        $this->_versions[$version->getName()] = $version;

        return $this;
    }

    /**
     * @param iterable<array|\DCarbone\PHPFHIR\Config\VersionConfig> $versions
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
        if ([] === $this->_versions) {
            return new \EmptyIterator();
        }
        return \SplFixedArray::fromArray($this->_versions, preserveKeys: false);
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
     * Set the path to place generated test classes.
     *
     * @param string|null $testsPath
     * @return self
     */
    public function setTestsPath(null|string $testsPath): self
    {
        if (null === $testsPath) {
            unset($this->_testsPath);
            return $this;
        }
        FileUtils::assertDirReadableAndWriteable($testsPath);
        $this->_testsPath = $testsPath;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTestsPath(): null|string
    {
        return $this->_testsPath ?? null;
    }

    /**
     * Set namespace prefix to apply to generated test classes.
     *
     * @param string $testsNamespacePrefix
     * @return self
     */
    public function setTestsNamespacePrefix(string $testsNamespacePrefix): self
    {
        $testsNamespacePrefix = trim($testsNamespacePrefix, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if ('' === $testsNamespacePrefix) {
            throw new \InvalidArgumentException('Test namespace prefix must not be empty');
        }

        if (!NameUtils::isValidNSName($testsNamespacePrefix)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Test namespace prefix "%s" is not a valid PHP namespace.',
                    $testsNamespacePrefix
                )
            );
        }

        $this->_testsNamespacePrefix = $testsNamespacePrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getTestsNamespacePrefix(): string
    {
        return $this->_testsNamespacePrefix;
    }

    /**
     * @return null|int
     */
    public function getLibrarySchemaLibxmlOpts(): null|int
    {
        return $this->_librarySchemaLibxmlOpts;
    }

    /**
     * @param int $librarySchemaLibxmlOpts
     * @return static
     */
    public function setLibrarySchemaLibxmlOpts(int $librarySchemaLibxmlOpts): self
    {
        $this->_librarySchemaLibxmlOpts = $librarySchemaLibxmlOpts;
        return $this;
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
        $ns = $this->getLibraryNamespacePrefix();
        if ($leadingSlash) {
            $ns = "\\{$ns}";
        }
        $bits = array_filter($bits);
        if ([] === $bits) {
            return $ns;
        }
        return sprintf('%s\\%s', $ns, implode('\\', $bits));
    }

    /**
     * Construct fully qualified name with the appropriate test namespace prefix applied
     *
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestName(bool $leadingSlash, string...$bits): string
    {
        return sprintf(
            "%s%s%s",
            $leadingSlash ? '\\' : '',
            $this->getTestsNamespacePrefix(),
            $this->getFullyQualifiedName(true, ...$bits)
        );
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

    public function getCoreTestFiles(): CoreFiles
    {
        if (isset($this->_coreTestFiles)) {
            return $this->_coreTestFiles;
        }
        if (!isset($this->_testsPath)) {
            throw new \RuntimeException('No tests path has been set.');
        }
        return $this->_coreTestFiles = new CoreFiles(
            $this,
            $this->getTestsPath(),
            PHPFHIR_TEMPLATE_TESTS_CORE_DIR,
            $this->getFullyQualifiedTestName(true),
        );
    }
}
