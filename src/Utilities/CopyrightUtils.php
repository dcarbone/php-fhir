<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DomainException;
use LogicException;
use RuntimeException;

/**
 * Class CopyrightUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class CopyrightUtils
{
    private static $_compiledWith;

    /** @var array */
    private static $_phpFHIRCopyright;

    /** @var array */
    private static $_fhirCopyright;

    /** @var string */
    private static $_basePHPFHIRCopyrightComment;

    /** @var string */
    private static $_fullPHPFHIRCopyrightComment;

    /** @var string */
    private static $_standardDate;

    /** @var string */
    private static $_fhirGenerationDate;
    /** @var string */
    private static $_fhirVersion;

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     */
    public static function compileCopyrights(VersionConfig $config)
    {
        if (isset(self::$_compiledWith) && self::$_compiledWith === $config) {
            return;
        }

        self::$_compiledWith = $config;

        self::$_standardDate = date('F jS, Y H:iO');

        self::$_phpFHIRCopyright = [
            'This class was generated with the PHPFHIR library (https://github.com/dcarbone/php-fhir) using',
            'class definitions from HL7 FHIR (https://www.hl7.org/fhir/)',
            '',
            sprintf('Class creation date: %s', self::$_standardDate),
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

        $fhirBase = sprintf('%s/fhir-base.xsd', $config->getSchemaPath());

        $config->getLogger()->debug(sprintf('Extracting FHIR copyright from "%s"...', $fhirBase));

        self::$_fhirCopyright = [];
        $fh = fopen($fhirBase, 'rb');
        if ($fh) {
            $inComment = false;
            while ($line = fgets($fh)) {
                $line = rtrim($line);

                if ('-->' === $line) {
                    break;
                }

                if ($inComment) {
                    $line = html_entity_decode($line);
                    self::$_fhirCopyright[] = $line;
                    $line = ltrim($line);
                    if (0 === strpos($line, 'Generated')) {
                        list($generated, $version) = explode('for FHIR', $line);

                        $generated = trim(str_replace('Generated on', '', $generated));
                        if ('' === $generated) {
                            throw new DomainException(
                                sprintf(
                                    'Unable to parse FHIR source generation date from line: %s',
                                    $line
                                )
                            );
                        } else {
                            self::$_fhirGenerationDate = $generated;
                        }

                        $version = trim($version);
                        if (0 === strpos($version, 'v')) {
                            self::$_fhirVersion = $version;
                        } else {
                            throw new LogicException(
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
            $config->getLogger()->critical($msg);
            throw new RuntimeException($msg);
        }

        self::$_basePHPFHIRCopyrightComment = sprintf(
            "/*!\n * %s\n */",
            implode("\n * ", self::$_phpFHIRCopyright)
        );

        self::$_fullPHPFHIRCopyrightComment = sprintf(
            "/*!\n * %s\n *\n * FHIR Copyright Notice:\n *\n * %s\n */",
            implode("\n * ", self::$_phpFHIRCopyright),
            implode("\n * ", self::$_fhirCopyright)
        );
    }

    /**
     * @return array
     */
    public static function getPHPFHIRCopyright()
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return self::$_phpFHIRCopyright;
    }

    /**
     * @return array
     */
    public static function getFHIRCopyright()
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return self::$_fhirCopyright;
    }

    /**
     * @return string
     */
    public static function getBasePHPFHIRCopyrightComment()
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return self::$_basePHPFHIRCopyrightComment;
    }

    /**
     * @return string
     */
    public static function getFullPHPFHIRCopyrightComment()
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return self::$_fullPHPFHIRCopyrightComment;
    }

    /**
     * @return string
     */
    public static function getStandardDate()
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return self::$_standardDate;
    }

    /**
     * @return string
     */
    public static function getFHIRGenerationDate()
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return self::$_fhirGenerationDate;
    }

    /**
     * @param bool $trimmed
     * @return string
     */
    public static function getFHIRVersion($trimmed)
    {
        if (!isset(self::$_compiledWith)) {
            throw new LogicException(
                sprintf(
                    'Cannot call %s before calling "compileCopyrights"',
                    __METHOD__
                )
            );
        }
        return $trimmed ? trim(self::$_fhirVersion, 'v') : self::$_fhirVersion;
    }
}