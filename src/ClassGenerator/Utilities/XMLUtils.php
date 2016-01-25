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

use DCarbone\PHPFHIR\ClassGenerator\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap;

/**
 * Class XMLUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class XMLUtils
{
    /**
     * @param string $filePath
     * @return \SimpleXMLElement
     */
    public static function constructSXEWithFilePath($filePath)
    {
        return self::_constructSXE(basename($filePath), file_get_contents($filePath));
    }

    /**
     * @param \SimpleXMLElement $extensionElement
     * @return null|string
     */
    public static function getBaseObjectName(\SimpleXMLElement $extensionElement)
    {
        if ('extension' !== $extensionElement->getName())
        {
            $xpath = $extensionElement->xpath('xs:complexContent/xs:extension');
            if (0 === count($xpath))
                $xpath = $extensionElement->xpath('xs:extension');

            if (0 === count($xpath))
                return null;

            $extensionElement = $xpath[0];
        }

        $attributes = $extensionElement->attributes();
        return (string)$attributes['base'];
    }

    /**
     * @param \SimpleXMLElement $restrictionElement
     * @return null|string
     */
    public static function getObjectRestrictionBaseName(\SimpleXMLElement $restrictionElement)
    {
        if ('restriction' !== $restrictionElement->getName())
        {
            $xpath = $restrictionElement->xpath('xs:complexContent/xs:restriction');
            if (0 === count($xpath))
                $xpath = $restrictionElement->xpath('xs:restriction');

            if (0 === count($xpath))
                return null;

            $restrictionElement = $xpath[0];
        }

        $attributes = $restrictionElement->attributes();

        if (isset($attributes['base']))
            return (string)$attributes['base'];

        return null;
    }

    /**
     * @param \SimpleXMLElement $sxe
     * @return null|string
     */
    public static function getObjectNameFromElement(\SimpleXMLElement $sxe)
    {
        $attributes = $sxe->attributes();

        if ($name = $attributes['name'])
            return (string)$name;

        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|\SimpleXMLElement
     */
    public static function getAnnotationElement(\SimpleXMLElement $parent)
    {
        $annotation = $parent->xpath('xs:annotation');
        if (1 === count($annotation))
            return $annotation[0];

        return null;
    }

    /**
     * @param \SimpleXMLElement $annotation
     * @return null|string|array
     */
    public static function getDocumentation(\SimpleXMLElement $annotation)
    {
        if ('annotation' !== $annotation->getName())
            $annotation = self::getAnnotationElement($annotation);

        if (null === $annotation)
            return null;

        $documentation = $annotation->xpath('xs:documentation');

        if (0 === count($documentation))
            return null;

        $return = array();
        foreach($documentation as $element)
        {
            $return[] = (string)$element;
        }
        return $return;
    }

    /**
     * @param string $xsdPath
     * @param string $outputNS
     * @return XSDMap
     */
    public static function buildXSDMap($xsdPath, $outputNS)
    {
        $xsdMap = new XSDMap();

        $fhirBaseXML = sprintf('%s/fhir-base.xsd', $xsdPath);

        if (!file_exists($fhirBaseXML))
        {
            throw new \RuntimeException(sprintf(
                'Unable to locate "fhir-base.xsd" at expected path "%s".',
                $fhirBaseXML
            ));
        }

        // First get class references in fhir-base.xsd
        self::parseClassesFromXSD(new \SplFileInfo($fhirBaseXML), $xsdMap, $outputNS);

        // Then scoop up the rest
        // TODO: Validate that, yes, certain files can be ignored.

        foreach(glob(sprintf('%s/*.xsd', $xsdPath), GLOB_NOSORT) as $file)
        {
            $basename = basename($file);

            if (0 === strpos($basename, 'fhir-'))
                continue;

            if ('xml.xsd' === $basename)
                continue;

            self::parseClassesFromXSD($file, $xsdMap, $outputNS);
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

            switch(strtolower($child->getName()))
            {
                case ElementTypeEnum::COMPLEX_TYPE:
                    $type = ClassTypeUtils::getComplexClassType($child);
                    $rootNS = NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getComplexTypeNamespace($fhirElementName, $type)
                    );
                    $className = NameUtils::getComplexTypeClassName($fhirElementName);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    $type = ClassTypeUtils::getSimpleClassType($fhirElementName);
                    $rootNS = NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getSimpleTypeNamespace($type)
                    );
                    $className = NameUtils::getSimpleTypeClassName($fhirElementName);
                    break;

                default: continue 2;
            }

            $nsSegments = explode('\\', $rootNS);
            if (0 === count($nsSegments))
                $pseudonym = sprintf('%sBase', $className);
            else
                $pseudonym = sprintf('%s%s', end($nsSegments), $className);

            $xsdMap[$fhirElementName] = array(
                'sxe' => $child,
                'elementName' => $fhirElementName,
                'rootNS' => $rootNS,
                'className' => $className,
                'pseudonym' => $pseudonym,
            );
        }
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
                $fileName,
                $error->message
            ));
        }

        throw new \RuntimeException(sprintf(
            'Unknown XML parsing error occurred while parsing "%s".',
            $fileName));
    }
}