<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Version;

class VersionCopyright
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $_config;
    /** @var \DCarbone\PHPFHIR\Version */
    private Version $_version;

    /** @var bool */
    private bool $_compiled = false;


    /** @var array */
    private array $_fhirCopyright;

    /** @var string */
    private string $_fullPHPFHIRCopyrightComment;

    /** @var string */
    private string $_fhirGenerationDate;
    /** @var string */
    private string $_fhirVersion;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     */
    public function __construct(Config $config, Version $version)
    {
        $this->_config = $config;
        $this->_version = $version;
    }

    public function compile(): void
    {
        if ($this->_compiled) {
            return;
        }

        $fhirBase = sprintf('%s/fhir-base.xsd', $this->_version->getSourcePath());

        $this->_config->getLogger()->debug(sprintf('Extracting FHIR copyright from "%s"...', $fhirBase));

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
            $this->_config->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        $this->_fullPHPFHIRCopyrightComment = sprintf(
            "/*!\n * %s\n *\n * FHIR Copyright Notice:\n *\n * %s\n */",
            implode("\n * ", $this->_config->getPHPFHIRCopyright()),
            implode("\n * ", $this->_fhirCopyright)
        );

        // flip it
        $this->_compiled = true;
    }

    /**
     * @return array
     */
    public function getFHIRCopyright(): array
    {
        $this->compile();
        return $this->_fhirCopyright;
    }

    /**
     * @return string
     */
    public function getFullPHPFHIRCopyrightComment(): string
    {
        $this->compile();
        return $this->_fullPHPFHIRCopyrightComment;
    }

    /**
     * @return string
     */
    public function getFHIRGenerationDate(): string
    {
        $this->compile();
        return $this->_fhirGenerationDate;
    }

    /**
     * @param bool $trimmed
     * @return string
     */
    public function getFHIRVersion(bool $trimmed): string
    {
        $this->compile();
        return $trimmed ? trim($this->_fhirVersion, 'v') : $this->_fhirVersion;
    }
}