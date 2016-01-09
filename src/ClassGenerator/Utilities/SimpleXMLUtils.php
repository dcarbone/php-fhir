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
 * Class SimpleXMLUtils
 * @package PHPFHIR\ClassGenerator\Utilities
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