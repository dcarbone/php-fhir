<?php namespace PHPFHIR\ClassGenerator\Utilities;

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
 * @package PHPFHIR\ClassGenerator\Utilities
 */
abstract class CopyrightUtils
{
    /** @var string */
    private static $_copyright = null;

    /**
     * @param string $xsdPath
     */
    public static function loadCopyright($xsdPath)
    {
        $today = date('F jS, Y');
        $comment = <<<STRING
/*!
 * This class was generated with the PHPFHIR library (https://github.com/dcarbone/php-fhir) using
 * class definitions from HL7 FHIR (https://www.hl7.org/fhir/)
 *
 * Class creation date: {$today}
 *
 * FHIR Copyright Notice:
 *
STRING;
        $fh = fopen($xsdPath.'fhir-all.xsd', 'r');
        $inComment = false;
        while($line = fgets($fh))
        {
            $line = trim($line);

            if ('-->' === $line)
                break;

            if ($inComment)
                $comment = sprintf("%s\n * %s", $comment, $line);

            if ('<!--' === $line)
                $inComment = true;
        }

        fclose($fh);

        self::$_copyright = sprintf("%s\n */", $comment);
    }

    /**
     * @return string
     */
    public static function getHL7Copyright()
    {
        return self::$_copyright;
    }
}