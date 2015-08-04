<?php namespace PHPFHIR\Utilities;

/**
 * Class CopyrightUtils
 * @package PHPFHIR\Utilities
 */
abstract class CopyrightUtils
{
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