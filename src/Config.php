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

use DCarbone\PHPFHIR\Enum\TestTypeEnum;
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
    /** @var \DCarbone\PHPFHIR\Logger */
    private Logger $_log;

    /** @var string */
    private string $_schemaPath;

    /** @var string */
    private string $_outputPath;

    /** @var string */
    private string $_rootNamespace;

    /** @var \DCarbone\PHPFHIR\Version[] */
    private array $_versions = [];

    /** @var bool */
    private bool $_silent = false;
    /** @var bool */
    private bool $_skipTests = false;
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

    /** @var array */
    private array $_versionsToGenerate;

    /**
     * Config constructor.
     * @param array $params
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $params = [], LoggerInterface $logger = null)
    {
        foreach (ConfigKeyEnum::required() as $key) {
            if (!isset($params[$key->value])) {
                throw new \DomainException(sprintf('Missing required configuration key "%s"', $key->value));
            }
            $this->{"set$key->value"}($params[$key->value]);
        }

        foreach (ConfigKeyEnum::optional() as $key) {
            if (isset($params[$key->value])) {
                $this->{"set$key->value"}($params[$key->value]);
            }
        }

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
            $this->getOutputPath(),
            PHPFHIR_TEMPLATE_CORE_DIR,
            $this->getFullyQualifiedName(true),
            $this->getFullyQualifiedTestsName(TestTypeEnum::BASE, true)
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
    public function getSchemaPath(): string
    {
        return $this->_schemaPath;
    }

    /**
     * @param string $schemaPath
     * @return $this
     */
    public function setSchemaPath(string $schemaPath): self
    {
        $this->_schemaPath = $schemaPath;
        return $this;
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
     * @return bool
     */
    public function isSkipTests(): bool
    {
        return $this->_skipTests;
    }

    /**
     * @param bool $skipTests
     * @return static
     */
    public function setSkipTests(bool $skipTests): self
    {
        $this->_skipTests = $skipTests;
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
     * @param array $versions
     * @return $this
     */
    public function setVersions(array $versions): self
    {
        $this->_versions = [];
        foreach ($versions as $name => $data) {
            $this->_versions[$name] = ($data instanceof Version) ? $data : new Version($this, $name, $data);
        }
        return $this;
    }

    /**
     * @param bool $limit If true, limits return to only versions that are set to be generated
     * @return \DCarbone\PHPFHIR\Version[]
     */
    public function getVersionsIterator(bool $limit = true): iterable
    {
        if (!$limit) {
            return \SplFixedArray::fromArray(array_values($this->_versions));
        }
        $out = [];
        foreach ($this->_versionsToGenerate as $vn) {
            $out[] = $this->_versions[$vn];
        }
        return \SplFixedArray::fromArray($out);
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
    public function listVersions(): array
    {
        return array_keys($this->_versions);
    }

    /**
     * Specify which versions are being generated this run.  An empty array assumes all.
     *
     * @param array $versionNames
     * @return $this
     */
    public function setVersionsToGenerate(array $versionNames): self
    {
        $this->_versionsToGenerate = [];
        if ([] === $versionNames) {
            return $this;
        }
        foreach (array_unique($versionNames, SORT_STRING) as $vn) {
            // test if this version is defined
            $this->getVersion($vn);
            // add to list.
            $this->_versionsToGenerate[] = $vn;
        }
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
        $ns = $leadingSlash ? "\\$this->_rootNamespace" : $this->_rootNamespace;
        $bits = array_filter($bits);
        if ([] === $bits) {
            return $ns;
        }
        return sprintf('%s\\%s', $ns, implode('\\', $bits));
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestTypeEnum $testType
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(TestTypeEnum $testType, bool $leadingSlash, string...$bits): string
    {
        return $this->getFullyQualifiedName($leadingSlash, $testType->namespaceSlug(), ...$bits);
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
