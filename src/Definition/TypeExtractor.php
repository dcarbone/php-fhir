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
use DCarbone\PHPFHIR\Definition\Extractor\SimpleTypeExtractor;
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\Utilities\XMLUtils;

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
        $config->getLogger()->debug(sprintf('Parsing classes from file "%s"...', $filePath));

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
            $config->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        $msg = sprintf(
            'Unknown XML parsing error occurred while parsing "%s".',
            $filename);
        $config->getLogger()->critical($msg);
        throw new \RuntimeException($msg);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    protected static function parseExtension(VersionConfig $config,
                                             Types $types,
                                             Type $type,
                                             \SimpleXMLElement $element)
    {
        TypeDecorator::determineTypeParentName($config, $type, $element);
        PropertyExtractor::extractTypeProperties($config, $types, $type, $element);
    }

    protected static function parseRestriction(VersionConfig $config,
                                               Types $types,
                                               Type $type,
                                               \SimpleXMLElement $element)
    {
        // TODO: Don't do this...
        $config->getLogger()->info(sprintf(
            'Ignoring restriction on Type %s',
            $type->getFHIRName()
        ));
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $outer
     */
    protected static function extractComplexInnards(VersionConfig $config,
                                                    Types $types,
                                                    Type $type,
                                                    \SimpleXMLElement $outer)
    {
        foreach ($outer->children('xs', true) as $element) {
            switch (strtolower($element->getName())) {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                    // immediate properties
                    PropertyExtractor::implementTypeProperty($config, $types, $type, $element);
                    break;

                case ElementTypeEnum::ANNOTATION:
                    // documentation!
                    $type->setDocumentation(XMLUtils::getDocumentation($element));
                    break;

                case ElementTypeEnum::COMPLEX_TYPE:
                case ElementTypeEnum::COMPLEX_CONTENT:
                    self::extractComplexInnards($config, $types, $type, $element);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                case ElementTypeEnum::SIMPLE_CONTENT:
                    static::extractSimpleInnards($config, $types, $type, $element);
                    break;

                case ElementTypeEnum::EXTENSION:
                    static::parseExtension($config, $types, $type, $element);
                    break;

                case ElementTypeEnum::RESTRICTION:
                    static::parseRestriction($config, $types, $type, $element);
                    break;

                default:
                    throw new \DomainException(sprintf(
                        'Unexpected Type %s first-level child %s: %s',
                        $type,
                        $element->getName(),
                        $element->saveXML()
                    ));
            }
        }
    }



    /**
     * Extract Type definitions present in XSD file
     *
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param string $file
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     */
    protected static function extractTypesFromXSD(VersionConfig $config, Types $types, $file)
    {
        $basename = basename($file);
        $config->getLogger()->startBreak("Extracting types from {$basename}");

        $sxe = static::constructSXEWithFilePath($file, $config);
        foreach ($sxe->children('xs', true) as $child) {
            /** @var \SimpleXMLElement $child */

            // skip these, might be nice at some point to be able to import the files out
            // of order.
            if ('include' === $child->getName() || 'import' === $child->getName()) {
                continue;
            }

            // fetch attributes, attempt to locate the name of the type being parsed
            $attributes = $child->attributes();
            $fhirElementName = (string)$attributes['name'];

            // if there was no attribute named "name", build some context then complain about it.
            if ('' === $fhirElementName) {
                $attrArray = [];
                foreach ($attributes as $attribute) {
                    /** @var \SimpleXMLElement $attribute */
                    $attrArray[] = sprintf('%s" : "%s', $attribute->getName(), (string)$attribute);
                }
                throw new \DomainException(sprintf(
                    'Unable to locate "name" attribute on element %s in file "%s" with attributes ["%s"]',
                    $child->getName(),
                    $basename,
                    implode('", "', $attrArray)
                ));
            }

            switch ($child->getName()) {
//                case XSDElementType::COMPLEX_TYPE:
//                    $type = $types->newType($fhirElementName, $child, $file);
//                    $sxe = $type->getSourceSXE();
//                    $name = XMLUtils::getObjectNameFromElement($sxe);
//                    if (false !== strpos($name, '.')) {
//                        TypeDecorator::determineComponentOfTypeName($config, $types, $type, $name);
//                    }
//                    static::extractComplexInnards($config, $types, $type, $type->getSourceSXE());
//                    $config->getLogger()->info(sprintf(
//                        'Located "Complex" Type class "%s\\%s" in file "%s"',
//                        $type->getFHIRTypeNamespace(),
//                        $type->getClassName(),
//                        $basename
//                    ));
//                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    $type = SimpleTypeExtractor::extract($config, $types, $file, $fhirElementName, $child);


//                    static::extractSimpleInnards($config, $types, $type, $type->getSourceSXE());
//                    $config->getLogger()->info(sprintf(
//                        'Located "Simple" Type class "%s\\%s" in file "%s"',
//                        $type->getFHIRTypeNamespace(),
//                        $type->getClassName(),
//                        $basename
//                    ));
                    break;

//                case XSDElementType::ELEMENT:
//                    $config->getLogger()->warning(sprintf(
//                        'Skipping root level element "%s" in file "%s"',
//                        $child->getName(),
//                        $basename
//                    ));
//                    break;

//                default:
//                    throw new \RuntimeException(sprintf(
//                        'Saw unexpected element "%s" in root of file "%s": %s',
//                        $child->getName(),
//                        $basename,
//                        $child->saveXML()
//                    ));
            }
        }

        $config->getLogger()->endBreak("Extracting types from {$basename}");
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @return \DCarbone\PHPFHIR\Definition\Types
     */
    public static function parseTypes(VersionConfig $config)
    {
        $types = new Types($config);

        $fhirBaseXSD = sprintf('%s/fhir-base.xsd', $config->getSchemaPath());

        if (!file_exists($fhirBaseXSD)) {
            $msg = sprintf(
                'Unable to locate "fhir-base.xsd" at expected path "%s".',
                $fhirBaseXSD
            );
            $config->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        // First get class references in fhir-base.xsd
        static::extractTypesFromXSD($config, $types, $fhirBaseXSD);

        // Then scoop up the rest
//        foreach (glob(sprintf('%s/*.xsd', $config->getSchemaPath()), GLOB_NOSORT) as $xsdFile) {
//            /** @var string $xsdFile */
//            $basename = basename($xsdFile);
//
//            if (0 === strpos($basename, 'fhir-')) {
//                $config->getLogger()->debug(sprintf('Skipping "aggregate" file "%s"', $xsdFile));
//                continue;
//            }
//
//            if ('xml.xsd' === $basename) {
//                $config->getLogger()->debug(sprintf('Skipping file "%s"', $xsdFile));
//                continue;
//            }
//
//            static::extractTypesFromXSD($config, $types, $xsdFile);
//        }

        return $types;
    }
}
