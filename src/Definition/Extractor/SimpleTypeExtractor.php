<?php

namespace DCarbone\PHPFHIR\Definition\Extractor;

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
use DCarbone\PHPFHIR\Definition\ListType;
use DCarbone\PHPFHIR\Definition\PrimitiveType;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Utilities\Element\SimpleTypeElementUtils;

/**
 * Class SimpleTypeExtractor
 * @package DCarbone\PHPFHIR\Definition\Extractor
 */
abstract class SimpleTypeExtractor
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param string $sourceFile
     * @param \SimpleXMLElement $element
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    protected static function handleDuplicateType(VersionConfig $config,
                                                  Types $types,
                                                  Type $type,
                                                  $sourceFile,
                                                  \SimpleXMLElement $element)
    {
        $config->getLogger()->warning(sprintf(
            'Seeing duplicate entry for SimpleType "%s".  First seen in file "%s", currently in "%s"',
            $type->getFHIRName(),
            $type->getSourceFilename(),
            $sourceFile
        ));

        // do shit...

        return $type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param string $sourceFile
     * @param string $fhirName
     * @param \SimpleXMLElement $element
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    protected static function buildPrimitiveType(VersionConfig $config,
                                                 Types $types,
                                                 $sourceFile,
                                                 $fhirName,
                                                 \SimpleXMLElement $element)
    {
        // construct new primitive type
        $type = new PrimitiveType($config, $fhirName, $element, $sourceFile);
        SimpleTypeElementUtils::decorateType($config, $types, $type, $element);
        return $type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param $sourceFile
     * @param $fhirName
     * @param \SimpleXMLElement $element
     * @return \DCarbone\PHPFHIR\Definition\ListType
     */
    protected static function buildListType(VersionConfig $config,
                                            Types $types,
                                            $sourceFile,
                                            $fhirName,
                                            \SimpleXMLElement $element)
    {
        $type = new ListType($config, $fhirName, $element, $sourceFile);
        SimpleTypeElementUtils::decorateType($config, $types, $type, $element);
        return $type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param string $sourceFile
     * @param string $fhirElementName
     * @param \SimpleXMLElement $element
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public static function extract(VersionConfig $config,
                                   Types $types,
                                   $sourceFile,
                                   $fhirElementName,
                                   \SimpleXMLElement $element)
    {
        // first, test to see if this type has already been created.
        // TODO: why am i seeing dupes...
        if (null !== ($type = $types->getTypeByName($fhirElementName))) {
            return self::handleDuplicateType($config, $types, $type, $sourceFile, $element);
        }

        if (false !== strpos($fhirElementName, '-primitive')) {
            return self::buildPrimitiveType($config, $types, $sourceFile, $fhirElementName, $element);
        }

        if (false !== strpos($fhirElementName, '-list')) {
            return self::buildListType($config, $types, $sourceFile, $fhirElementName, $element);
        }

//        if (null === ()) {
//            // create new type
//            $type = $types->newType($fhirElementName, $child, $file);
//            // being a simple type,
//            $valueType = $types->newPrimitiveTypeValueType($fhirElementName);
//            $type->addProperty(
//                new Property($config,
//                    'value',
//                    $valueType->getFHIRName(),
//                    $type->getSourceSXE())
//            );
//        }
    }
}