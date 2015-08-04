<?php namespace PHPFHIR\Utilities;

/**
 * Class CopyrightUtil
 * @package PHPFHIR\Utilities
 */
abstract class CopyrightUtil
{
    private static $_copyright = null;

    /**
     * @param string $xsdPath
     */
    public static function loadCopyright($xsdPath)
    {
        $comment = '';
        $fh = fopen($xsdPath.'fhir-all.xsd', 'r');
        $inComment = false;
        while($line = fgets($fh))
        {
            $line = trim($line);

            if ('-->' === $line)
                break;

            if ($inComment)
                $comment = sprintf("%s\n%s", $comment, $line);

            if ('<!--' === $line)
                $inComment = true;
        }

        fclose($fh);

        self::$_copyright = $comment;
    }

    /**
     * @return string
     */
    public static function getHL7Copyright()
    {
        return self::$_copyright;
    }
}