<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition\Decorator;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

// TODO: this was only seen when parsing the ATOM file, which I currently have no plans on supporting.

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Property;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use SimpleXMLElement;

/**
 * Class SimpleTypeElementPropertyDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
abstract class SimpleTypeElementPropertyDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $simpleType
     */
    public static function decorate(
        VersionConfig $config,
        Types $types,
        Type $type,
        Property $property,
        SimpleXMLElement $simpleType
    ): void {
        // parse through attributes
        foreach ($simpleType->attributes() as $attribute) {
            switch ($attribute->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $simpleType, $attribute);
            }
        }

        foreach ($simpleType->children('xs', true) as $child) {
            switch ($child->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $simpleType, $child);
            }
        }
    }
}