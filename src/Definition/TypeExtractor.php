<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Utilities\ClassTypeUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Utilities\NSUtils;
use DCarbone\PHPFHIR\Utilities\XMLUtils;

/**
 * Class TypeExtractor
 * @package DCarbone\PHPFHIR
 */
abstract class TypeExtractor
{
    /**
     * @param string $filePath
     * @param \DCarbone\PHPFHIR\Config $config
     * @return \SimpleXMLElement
     */
    protected static function constructSXEWithFilePath($filePath, Config $config)
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    protected static function parseExtensionOrRestriction(Config $config,
                                                          Types $types,
                                                          Type $type,
                                                          \SimpleXMLElement $element)
    {
        TypeRelationshipBuilder::determineParent($config, $types, $type, $element);
        PropertyExtractor::extractTypeProperties($config, $types, $type, $element);
    }

    /**
     * Loop through first-level type definition children to try to extract:
     *
     * - Documentation
     * - Properties
     * - Parent Base Type (if applicable)
     *
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $outer
     */
    protected static function extractInnards(Config $config, Types $types, Type $type, \SimpleXMLElement $outer)
    {
        foreach ($outer->children('xs', true) as $element) {
            switch (strtolower($element->getName())) {
                case ElementTypeEnum::ATTRIBUTE:
                case ElementTypeEnum::CHOICE:
                case ElementTypeEnum::SEQUENCE:
                case ElementTypeEnum::UNION:
                case ElementTypeEnum::ENUMERATION:
                    // immediate properties
                    PropertyExtractor::implementTypeProperty($config, $types, $type, $element);
                    break;

                case ElementTypeEnum::ANNOTATION:
                    // documentation!
                    $type->setDocumentation(XMLUtils::getDocumentation($element));
                    break;

                case ElementTypeEnum::COMPLEX_TYPE:
                case ElementTypeEnum::COMPLEX_CONTENT:
                case ElementTypeEnum::SIMPLE_TYPE:
                case ElementTypeEnum::SIMPLE_CONTENT:
                    // sub-types
                    static::extractInnards($config, $types, $type, $element);
                    break;

                case ElementTypeEnum::RESTRICTION:
                case ElementTypeEnum::EXTENSION:
                    // we've got a parent
                    static::parseExtensionOrRestriction($config, $types, $type, $element);
                    break;

                default:
                    $config->getLogger()->warning(sprintf(
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
     * @param \DCarbone\PHPFHIR\Config $config
     */
    protected static function extractTypesFromXSD(Config $config, Types $types, $file)
    {
        $config->getLogger()->debug(sprintf('Parsing classes from file "%s"...', $file));

        $sxe = static::constructSXEWithFilePath($file, $config);
        foreach ($sxe->children('xs', true) as $child) {
            /** @var \SimpleXMLElement $child */
            $attributes = $child->attributes();
            $fhirElementName = (string)$attributes['name'];
            $type = new Type($config, $child, $fhirElementName);

            if ('' === $fhirElementName) {
                $attrArray = [];
                foreach ($attributes as $attribute) {
                    /** @var \SimpleXMLElement $attribute */
                    $attrArray[] = sprintf('%s : %s', $attribute->getName(), (string)$attribute);
                }
                $config->getLogger()
                       ->warning(sprintf(
                           'Unable to locate "name" attribute on element in file "%s" with attributes ["%s"]',
                           $file,
                           implode('", "', $attrArray)
                       ));
                continue;
            }

            if (ElementTypeEnum::COMPLEX_TYPE === strtolower($child->getName())) {
                $types->addType($type);
                ClassTypeUtils::parseComplexClassType($config, $type);
                static::extractInnards($config, $types, $type, $type->getSourceSXE());

                $type
                    ->setNamespace(NSUtils::generateRootNamespace(
                        $config,
                        NSUtils::getComplexTypeNamespace($fhirElementName, $type)
                    ))
                    ->setClassName(NameUtils::getComplexTypeClassName($fhirElementName));

                $config->getLogger()->info(sprintf(
                    'Located "%s" class "%s\\%s" in file "%s"',
                    $type->getBaseType(),
                    $type->getNamespace(),
                    $type->getClassName(),
                    basename($file)
                ));
            } else {
                $config->getLogger()->warning(sprintf(
                    'Saw non-complex element "%s" in root: %s',
                    $child->getName(),
                    $child
                ));
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @return \DCarbone\PHPFHIR\Definition\Types
     */
    public static function parseTypes(Config $config)
    {
        $types = new Types($config);

        $fhirBaseXSD = sprintf('%s/fhir-base.xsd', $config->getXSDPath());

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
        foreach (glob(sprintf('%s/*.xsd', $config->getXSDPath()), GLOB_NOSORT) as $xsdFile) {
            /** @var string $xsdFile */
            $basename = basename($xsdFile);

            if (0 === strpos($basename, 'fhir-')) {
                $config->getLogger()->debug(sprintf('Skipping "aggregate" file "%s"', $xsdFile));
                continue;
            }

            if ('xml.xsd' === $basename) {
                $config->getLogger()->debug(sprintf('Skipping file "%s"', $xsdFile));
                continue;
            }

            static::extractTypesFromXSD($config, $types, $xsdFile);
        }

        return $types;
    }
}
