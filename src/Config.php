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

use DCarbone\PHPFHIR\Enum\TestType;
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
    private string $schemaPath;

    /** @var string */
    private string $classesPath;

    /** @var string */
    private string $rootNamespace;

    /** @var \DCarbone\PHPFHIR\Version[] */
    private array $versions = [];

    /** @var bool */
    private bool $silent = false;
    /** @var bool */
    private bool $skipTests = false;
    /** @var int|null */
    private ?int $libxmlOpts;

    /** @var string */
    private string $_standardDate;

    /** @var array */
    private array $_phpFHIRCopyright;
    /** @var string */
    private string $_basePHPFHIRCopyrightComment;

    /**
     * Config constructor.
     * @param array $params
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $params = [], LoggerInterface $logger = null)
    {
        foreach (ConfigKeys::required() as $key) {
            if (!isset($params[$key->value])) {
                throw new \DomainException(sprintf('Missing required configuration key "%s"', $key->value));
            }
            $this->{"set$key->value"}($params[$key->value]);
        }

        foreach (ConfigKeys::optional() as $key) {
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
        return $this->schemaPath;
    }

    /**
     * @param string $schemaPath
     * @return $this
     */
    public function setSchemaPath(string $schemaPath): self
    {
        $this->schemaPath = $schemaPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getRootNamespace(): string
    {
        return $this->rootNamespace;
    }

    /**
     * @param string $rootNamespace
     * @return self
     */
    public function setRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = $rootNamespace;
        return $this;
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
    public function getLibxmlOpts(): ?int
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
        foreach ($versions as $name => $data) {
            $this->versions[$name] = ($data instanceof Version) ? $data : new Version($this, $name, $data);
        }
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version[]
     */
    public function getVersionsIterator(): \Iterator
    {
        return \SplFixedArray::fromArray($this->versions);
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
     * @return \DCarbone\PHPFHIR\Version
     */
    public function getVersion(string $version): Version
    {
        if (!$this->hasVersion($version)) {
            throw new \OutOfBoundsException(sprintf(
                    'No version with name "%s" has been configured.  Available: ["%s"]',
                    $version,
                    implode('", "', array_keys($this->versions)),
                )
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

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string...$bits): string
    {
        $ns = $leadingSlash ? "\\$this->rootNamespace" : $this->rootNamespace;
        $bits = array_filter($bits);
        if ([] === $bits) {
            return $ns;
        }
        return sprintf('%s\\%s', $ns, implode('\\', $bits));
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestType $testType
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(TestType $testType, bool $leadingSlash, string...$bits): string
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
     * @return string
     */
    public function getBasePHPFHIRCopyrightComment(): string
    {
        return $this->_basePHPFHIRCopyrightComment;
    }
}
