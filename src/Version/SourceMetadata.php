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

class SourceMetadata
{
    private string $_schemaPath;

    private bool $_compiled = false;

    private array $_fhirCopyright;

    private string $_fhirGenerationDate;
    private string $_fhirVersion;

    /**
     * The base version string with any pre-release suffix stripped.
     * e.g. 'v6.0.0-ballot4' -> 'v6.0.0'.
     * Used for all Semver range comparisons.
     */
    private string $_fhirVersionBase;

    /**
     * The pre-release identifier, if any (e.g. 'ballot4').  Null for GA releases.
     */
    private null|string $_fhirPreRelease = null;

    public function __construct(string $schemPath)
    {
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
     * Return the shortenend representation of the FHIR semantic version containing only Major.Minor values.
     *
     * @return string
     */
    public function getShortVersion(): string
    {
        $this->_compile();
        $v = ltrim($this->_fhirVersionBase, 'v');
        return match (substr_count($v, '.')) {
            1 => $v,
            2 => substr($v, 0, strrpos($v, '.')),
            default => implode('.', array_chunk(explode('.', $v), 2)[0])
        };
    }

    /**
     * Returns an integer representation of the FHIR semantic version.
     * Pre-release suffixes are ignored; the integer is derived from the base
     * version only, so 'v6.0.0-ballot4' and 'v6.0.0' produce the same value.
     * Use isPreRelease() to distinguish ballot from GA releases.
     *
     * @return int
     */
    public function getVersionInteger(): int
    {
        $this->_compile();
        return intval(sprintf("%'.-08s", str_replace(['v', '.'], '', $this->_fhirVersionBase)));
    }

    /**
     * Returns the pre-release identifier extracted from the FHIR version string,
     * or null if this is a GA release.
     *
     * e.g. 'v6.0.0-ballot4' -> 'ballot4'
     *      'v5.0.0'         -> null
     *
     * @return string|null
     */
    public function getPreRelease(): null|string
    {
        $this->_compile();
        return $this->_fhirPreRelease;
    }

    /**
     * Returns true when the FHIR source version carries a pre-release suffix
     * (e.g. '-ballot4').
     *
     * @return bool
     */
    public function isPreRelease(): bool
    {
        $this->_compile();
        return null !== $this->_fhirPreRelease;
    }

    /**
     * Returns true if the upstream source was generated from, or is based on, DSTU1.
     *
     * @return bool
     */
    public function isDSTU1(): bool
    {
        return Semver::satisfies($this->_getVersionBase(), '< v1.0.0');
    }

    public function isDSTU2(): bool
    {
        $base = $this->_getVersionBase();
        return Semver::satisfies($base, '>= v1.0.0')
            && Semver::satisfies($base, '< v3.0.0');
    }

    public function isSTU3(): bool
    {
        $base = $this->_getVersionBase();
        return Semver::satisfies($base, '>= v3.0.0')
            && Semver::satisfies($base, '< v4.0.0');
    }

    public function isR4(): bool
    {
        $base = $this->_getVersionBase();
        return Semver::satisfies($base, '>= v4.0.0')
            && Semver::satisfies($base, '< v4.3.0');
    }

    public function isR4B(): bool
    {
        $base = $this->_getVersionBase();
        return Semver::satisfies($base, '>= v4.3.0')
            && Semver::satisfies($base, '< v5.0.0');
    }

    public function isR5(): bool
    {
        $base = $this->_getVersionBase();
        return Semver::satisfies($base, '>= v5.0.0')
            && Semver::satisfies($base, '< v6.0.0');
    }

    public function isR6(): bool
    {
        $base = $this->_getVersionBase();
        return Semver::satisfies($base, '>= v6.0.0')
            && Semver::satisfies($base, '< v7.0.0');
    }

    /**
     * Returns the compiled base version (without pre-release suffix) for use
     * in Semver range comparisons.
     */
    private function _getVersionBase(): string
    {
        $this->_compile();
        return $this->_fhirVersionBase;
    }

    private function _compile(): void
    {
        if ($this->_compiled) {
            return;
        }

        $fhirBase = sprintf('%s/fhir-base.xsd', $this->_schemaPath);

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
                            // Split off any pre-release suffix (e.g. 'v6.0.0-ballot4').
                            $dashPos = strpos($version, '-');
                            if (false !== $dashPos) {
                                $this->_fhirVersionBase  = substr($version, 0, $dashPos);
                                $this->_fhirPreRelease   = substr($version, $dashPos + 1);
                            } else {
                                $this->_fhirVersionBase = $version;
                                $this->_fhirPreRelease  = null;
                            }
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
            throw new \RuntimeException($msg);
        }

        // flip it
        $this->_compiled = true;
    }
}