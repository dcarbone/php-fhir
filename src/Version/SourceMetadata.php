<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

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

use Composer\Semver\Semver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class SourceMetadata implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const _DSTU1_VERSION_MAX = '0.0.82';
    private const _R4B_VERSION = '4.3.0';

    private string $_schemaPath;

    private bool $_compiled = false;

    private array $_fhirCopyright;

    private string $_fhirGenerationDate;
    private string $_fhirVersion;

    public function __construct(LoggerInterface $log, string $schemPath)
    {
        $this->setLogger($log);
        $this->_schemaPath = $schemPath;
    }

    /**
     * @return array
     */
    public function getFHIRCopyright(): array
    {
        $this->_compile();
        return $this->_fhirCopyright;
    }

    /**
     * @return string
     */
    public function getSourceGenerationDate(): string
    {
        $this->_compile();
        return $this->_fhirGenerationDate;
    }

    /**
     * @param bool $trimmed If true, trims off the 'v' prefix before returning.
     * @return string
     */
    public function getSemanticVersion(bool $trimmed): string
    {
        $this->_compile();
        return $trimmed ? ltrim($this->_fhirVersion, 'v') : $this->_fhirVersion;
    }

    /**
     * Return the shortenend representation of the FHIR semantic version containing only Manjor.Minor values.
     *
     * @return string
     */
    public function getShortVersion(): string
    {
        $this->_compile();
        $v = ltrim($this->_fhirVersion);
        return match (substr_count($v, '.')) {
            1 => $v,
            2 => substr($v, 0, strrpos($v, '.')),
            default => implode('.', array_chunk(explode('.', $v), 2)[0])
        };
    }

    /**
     * Returns an integer representation of the FHIR semantic version.
     *
     * @return int
     */
    public function getVersionInteger(): int
    {
        $this->_compile();
        return intval(sprintf("%'.-08s", str_replace(['v', '.'], '', $this->_fhirVersion)));
    }

    /**
     * Returns true if the upstream source was generated from, or is based on, DSTU1.
     *
     * @return bool
     */
    public function isDSTU1(): bool
    {
        return Semver::satisfies($this->getSemanticVersion(false), '<= ' . self::_DSTU1_VERSION_MAX);
    }

    public function isR4B(): bool
    {
        return Semver::satisfies($this->getSemanticVersion(false), '== ' . self::_R4B_VERSION);
    }

    private function _compile(): void
    {
        if ($this->_compiled) {
            return;
        }

        $fhirBase = sprintf('%s/fhir-base.xsd', $this->_schemaPath);

        $this->logger->debug(sprintf('Extracting FHIR version metadata from "%s"...', $fhirBase));

        $this->_fhirCopyright = [];
        $fh = fopen($fhirBase, 'rb');
        if ($fh) {
            $inComment = false;
            while ($line = fgets($fh)) {
                $line = rtrim($line);

                if ('-->' === $line) {
                    break;
                }

                if ($inComment) {
                    // needed as sometimes their comment generation breaks...
                    $line = str_replace(['/*', '*/'], '', $line);

                    $line = html_entity_decode($line);
                    $this->_fhirCopyright[] = $line;
                    $line = ltrim($line);
                    if (str_starts_with($line, 'Generated on ')) {
                        [$generated, $version] = explode('for FHIR', $line);

                        $generated = trim(str_replace('Generated on', '', $generated));
                        if ('' === $generated) {
                            throw new \DomainException(
                                sprintf(
                                    'Unable to parse FHIR source generation date from line: %s',
                                    $line
                                )
                            );
                        } else {
                            $this->_fhirGenerationDate = $generated;
                        }

                        $version = trim($version);
                        if (str_starts_with($version, 'v')) {
                            $this->_fhirVersion = $version;
                        } else {
                            throw new \LogicException(
                                sprintf(
                                    'Unable to determine FHIR version from: %s',
                                    $line
                                )
                            );
                        }
                    }
                } elseif ('<!--' === $line) {
                    $inComment = true;
                }
            }

            fclose($fh);
        } else {
            $msg = sprintf(
                '%s::compileCopyrights - Unable to open %s to extract FHIR copyright.',
                get_called_class(),
                $fhirBase
            );
            $this->logger->critical($msg);
            throw new \RuntimeException($msg);
        }

        // flip it
        $this->_compiled = true;
    }
}