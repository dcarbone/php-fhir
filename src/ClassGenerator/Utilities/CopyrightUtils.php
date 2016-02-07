<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class CopyrightUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class CopyrightUtils
{
    /** @var array */
    private static $_phpFHIRCopyright = array();

    /** @var array */
    private static $_fhirCopyright = array();

    /** @var string */
    private static $_basePHPFHIRCopyrightComment = '';

    /** @var string */
    private static $_fullPHPFHIRCopyrightComment = '';

    /** @var string */
    private static $_standardDate;

    /**
     * @param string $xsdPath
     */
    public static function compileCopyrights($xsdPath)
    {
        self::$_standardDate = date('F jS, Y');

        self::$_phpFHIRCopyright = array(
            'This class was generated with the PHPFHIR library (https://github.com/dcarbone/php-fhir) using',
            'class definitions from HL7 FHIR (https://www.hl7.org/fhir/)',
            '',
            sprintf('Class creation date: %s', self::$_standardDate),
            '',
            'PHPFHIR Copyright:',
            '',
            'Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)',
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
            ''
        );

        $fhirBase = sprintf('%s/fhir-base.xsd', $xsdPath);
        $fh = fopen($fhirBase, 'rb');
        if ($fh)
        {
            $inComment = false;
            while($line = fgets($fh))
            {
                $line = rtrim($line);

                if ('-->' === $line)
                    break;

                if ($inComment)
                    self::$_fhirCopyright[] = html_entity_decode($line);

                if ('<!--' === $line)
                    $inComment = true;
            }

            fclose($fh);
        }
        else
        {
            throw new \RuntimeException(sprintf(
                '%s::compileCopyrights - Unable to open %s to extract FHIR copyright.',
                get_called_class(),
                $fhirBase
            ));
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
        return self::$_phpFHIRCopyright;
    }

    /**
     * @return array
     */
    public static function getFHIRCopyright()
    {
        return self::$_fhirCopyright;
    }

    /**
     * @return string
     */
    public static function getBasePHPFHIRCopyrightComment()
    {
        return self::$_basePHPFHIRCopyrightComment;
    }

    /**
     * @return string
     */
    public static function getFullPHPFHIRCopyrightComment()
    {
        return self::$_fullPHPFHIRCopyrightComment;
    }

    /**
     * @return string
     */
    public static function getStandardDate()
    {
        return self::$_standardDate;
    }
}