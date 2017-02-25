<?php namespace DCarbone\PHPFHIR\ClassGenerator\Generator;

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

use DCarbone\PHPFHIR\ClassGenerator\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\ClassTypeUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NSUtils;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class XSDMapGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Generator
 */
abstract class XSDMapGenerator
{
    /**
     * @param string $xsdPath
     * @param string $outputNS
     * @return XSDMap
     */
    public static function buildXSDMap($xsdPath, $outputNS)
    {
        $xsdMap = new XSDMap();

        $fhirBaseXSD = sprintf('%s/fhir-base.xsd', $xsdPath);

        if (!file_exists($fhirBaseXSD))
        {
            throw new \RuntimeException(sprintf(
                'Unable to locate "fhir-base.xsd" at expected path "%s".',
                $fhirBaseXSD
            ));
        }

        // First get class references in fhir-base.xsd
        self::parseClassesFromXSD($fhirBaseXSD, $xsdMap, $outputNS);

        // Then scoop up the rest
        foreach(glob(sprintf('%s/*.xsd', $xsdPath), GLOB_NOSORT) as $xsdFile)
        {
            $basename = basename($xsdFile);

            if (0 === strpos($basename, 'fhir-'))
                continue;

            if ('xml.xsd' === $basename)
                continue;

            self::parseClassesFromXSD($xsdFile, $xsdMap, $outputNS);
        }

        return $xsdMap;
    }

    /**
     * @param string $file
     * @param XSDMap $xsdMap
     * @param string $outputNS
     */
    public static function parseClassesFromXSD($file, XSDMap $xsdMap, $outputNS)
    {
        $sxe = self::constructSXEWithFilePath($file);
        foreach($sxe->children('xs', true) as $child)
        {
            /** @var \SimpleXMLElement $child */
            $attributes = $child->attributes();
            $fhirElementName = (string)$attributes['name'];

            if ('' === $fhirElementName)
                continue;

            if (ElementTypeEnum::COMPLEX_TYPE === strtolower($child->getName()))
            {
                $type = ClassTypeUtils::getComplexClassType($child);

                $xsdMap[$fhirElementName] = new XSDMap\XSDMapEntry(
                    $child,
                    $fhirElementName,
                    NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getComplexTypeNamespace($fhirElementName, $type)
                    ),
                    NameUtils::getComplexTypeClassName($fhirElementName)
                );
            }
        }
    }

    /**
     * @param string $filePath
     * @return \SimpleXMLElement
     */
    public static function constructSXEWithFilePath($filePath)
    {
        $filename = basename($filePath);

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement(file_get_contents($filePath), LIBXML_COMPACT | LIBXML_NSCLEAN);
        libxml_use_internal_errors(false);

        if ($sxe instanceof \SimpleXMLElement)
        {
            $sxe->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
            $sxe->registerXPathNamespace('', 'http://hl7.org/fhir');
            return $sxe;
        }

        $error = libxml_get_last_error();
        if ($error)
        {
            throw new \RuntimeException(sprintf(
                'Error occurred while parsing file "%s": "%s"',
                $filename,
                $error->message
            ));
        }

        throw new \RuntimeException(sprintf(
            'Unknown XML parsing error occurred while parsing "%s".',
            $filename));
    }
}