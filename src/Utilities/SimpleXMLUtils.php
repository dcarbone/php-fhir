<?php namespace PHPFHIR\Utilities;

/**
 * Class SimpleXMLUtils
 * @package PHPFHIR\Utilities
 */
abstract class SimpleXMLUtils
{
    /**
     * @param string $filePath
     * @return \SimpleXMLElement
     */
    public static function constructWithFilePath($filePath)
    {
        return self::_constructSXE(basename($filePath), file_get_contents($filePath));
    }

    /**
     * @param \SplFileInfo $file
     * @return \SimpleXMLElement
     */
    public static function constructWithFileInfo(\SplFileInfo $file)
    {
        return self::_constructSXE($file->getBasename(true), file_get_contents($file->getRealPath()));
    }

    /**
     * @param string $fileName
     * @param string $contents
     * @return \SimpleXMLElement
     */
    private static function _constructSXE($fileName, $contents)
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement($contents, LIBXML_COMPACT | LIBXML_NSCLEAN);

        if ($sxe instanceof \SimpleXMLElement)
        {
            libxml_use_internal_errors(false);
            $sxe->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
            $sxe->registerXPathNamespace('', 'http://hl7.org/fhir');
            return $sxe;
        }

        $error = libxml_get_last_error();
        if ($error)
        {
            throw new \RuntimeException(sprintf(
                'Error occurred while parsing file "%s": "%s"',
                $fileName,
                $error->message
            ));
        }

        throw new \RuntimeException(sprintf(
            'Unknown XML parsing error occurred while parsing "%s".',
            $fileName));
    }
}