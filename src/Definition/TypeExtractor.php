<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Definition\Decorator\ComplexTypeElementTypeDecorator;
use DCarbone\PHPFHIR\Definition\Decorator\ElementElementTypeDecorator;
use DCarbone\PHPFHIR\Definition\Decorator\SimpleTypeElementTypeDecorator;
use DCarbone\PHPFHIR\Enum\ElementNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class TypeExtractor
 * @package DCarbone\PHPFHIR
 */
abstract class TypeExtractor
{
    /**
     * @param string $filePath
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @return \SimpleXMLElement
     */
    protected static function constructSXEWithFilePath($filePath, VersionConfig $config)
    {
        $logger = $config->getLogger();
        $logger->debug(sprintf('Parsing classes from file "%s"...', $filePath));

        $filename = basename($filePath);

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement(file_get_contents($filePath), LIBXML_COMPACT | LIBXML_NSCLEAN);
        libxml_use_internal_errors(false);

        if ($sxe instanceof \SimpleXMLElement) {
            $sxe->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
            $sxe->registerXPathNamespace('', 'http://hl7.org/fhir');
            return $sxe;
        }

        $error = libxml_get_last_error();
        if ($error) {
            $msg = sprintf(
                'Error occurred while parsing file "%s": "%s"',
                $filename,
                $error->message
            );
            $logger->critical($msg);
            throw new \RuntimeException($msg);
        }

        $msg = sprintf(
            'Unknown XML parsing error occurred while parsing "%s".',
            $filename);
        $logger->critical($msg);
        throw new \RuntimeException($msg);
    }

    /**
     * Extract Type definitions present in XSD file
     *
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param string $sourceFile
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     */
    protected static function extractTypesFromXSD(VersionConfig $config, Types $types, $sourceFile)
    {
        $basename = basename($sourceFile);
        $logger = $config->getLogger();

        $logger->startBreak("Extracting types from {$basename}");

        $sxe = static::constructSXEWithFilePath($sourceFile, $config);
        foreach ($sxe->children('xs', true) as $child) {
            /** @var \SimpleXMLElement $child */

            $childName = $child->getName();

            // skip these root level elements
            if (ElementNameEnum::_INCLUDE === $childName || ElementNameEnum::IMPORT === $childName || ElementNameEnum::ANNOTATION === $childName) {
                continue;
            }

            // fetch attributes, attempt to locate the name of the type being parsed
            $attributes = $child->attributes();
            $fhirName = (string)$attributes['name'];

            // if there was no attribute named "name", build some context then complain about it.
            if ('' === $fhirName) {
                throw new \DomainException(sprintf(
                    'Unable to locate "name" attribute on element "%s" in file "%s": %s',
                    $childName,
                    $basename,
                    $child->saveXML()
                ));
            }

            // parse top level elements
            switch ($childName) {
                case ElementNameEnum::SIMPLE_TYPE:
                    $logger->debug(sprintf('Parsing "%s" from SimpleType', $fhirName));
                    // build type
                    $type = new Type($config, $fhirName, $child, $sourceFile);

                    // add type
                    $types->addType($type);

                    // proceed with decoration
                    SimpleTypeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                case ElementNameEnum::COMPLEX_TYPE:
                    $logger->debug(sprintf('Parsing "%s" from ComplexType', $fhirName));
                    // build type
                    $type = new Type($config, $fhirName, $child, $sourceFile);

                    // add type
                    $types->addType($type);

                    // proceed with decoration
                    ComplexTypeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                case ElementNameEnum::ELEMENT:
                    /* TODO: this is producing some oddities as the result of things like this:
                     * src: R4 bundle.xsd
                     * <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns="http://hl7.org/fhir" xmlns:xhtml="http://www.w3.org/1999/xhtml" targetNamespace="http://hl7.org/fhir" elementFormDefault="qualified" version="1.0">
                     * <xs:element name="Bundle" type="Bundle">
                     *
                     * </xs:element>
                     * <xs:complexType name="Bundle">
                     *
                     * </xs:complexType>
                     *
                     * this may be ignorable, but recording for posterity.
                     */

                    $logger->debug(sprintf('Parsing "%s" from Element', $fhirName));
                    // build type
                    $type = new Type($config, $fhirName, $child, $sourceFile);

                    // add type
                    $types->addType($type);

                    // proceed with decoration
                    ElementElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedRootElementException($child, $sourceFile);
            }
        }

        $logger->endBreak("Extracting types from {$basename}");
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @return \DCarbone\PHPFHIR\Definition\Types
     */
    public static function parseTypes(VersionConfig $config)
    {
        $types = new Types($config);
        $logger = $config->getLogger();

        // first, parse all .xsd files without the "fhir-" prefix
        foreach (glob(sprintf('%s/*.xsd', $config->getSchemaPath()), GLOB_NOSORT) as $xsdFile) {
            $basename = basename($xsdFile);
            if (0 === strpos($basename, PHPFHIR_SKIP_FHIR_XSD_PREFIX)) {
                continue;
            }
            if (0 === strpos($basename, PHPFHIR_SKIP_ATOM_XSD_PREFIX)) {
                continue;
            }
            if (PHPFHIR_SKIP_XML_XSD === $basename || PHPFHIR_SKIP_XHTML_XSD === $basename || PHPFHIR_SKIP_TOMBSTONE_XSD === $basename) {
                $logger->debug(sprintf('Skipping file "%s"', $xsdFile));
                continue;
            }
            static::extractTypesFromXSD($config, $types, $xsdFile);
        }

        // next, parse the "fhir-" prefixed ones.
        // of most interest:
        //  - fhir-base.xsd: contains primitive types, base Element, Resource, ResourceContainer, etc...
        //  - fhir-single.xsd: ideally this would just be full of dupes found in other files, but in practice there
        //    are often types only defined in this file that are not defined in the specific individual files.
        foreach (glob(sprintf('%s/fhir-*.xsd', $config->getSchemaPath()), GLOB_NOSORT) as $xsdFile) {
            $basename = basename($xsdFile);
            if (PHPFHIR_SKIP_XML_XSD === $basename || PHPFHIR_SKIP_XHTML_XSD === $basename) {
                $logger->debug(sprintf('Skipping file "%s"', $xsdFile));
                continue;
            }
            if (0 === strpos($basename, PHPFHIR_SKIP_ATOM_XSD_PREFIX)) {
                continue;
            }
            static::extractTypesFromXSD($config, $types, $xsdFile);
        }

        return $types;
    }
}
