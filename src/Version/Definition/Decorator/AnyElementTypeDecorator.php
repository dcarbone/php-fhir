<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition\Decorator;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * This decorator is seemingly only used within DSTU1.  It creates a new property and sets it to the XHTML type.
 */
abstract class AnyElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $any
     */
    public static function decorate(Config $config, Types $types, Type $type, \SimpleXMLElement $any): void
    {
        $namespace = '';
        $minOccurs = '';
        $maxOccurs = '';

        // parse through attributes
        foreach ($any->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::NAMESPACE->value:
                    $namespace = (string)$attribute;
                    break;
                case AttributeNameEnum::MIN_OCCURS->value:
                    $minOccurs = (string)$attribute;
                    break;
                case AttributeNameEnum::MAX_OCCURS->value:
                    $maxOccurs = (string)$attribute;
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $any, $attribute);
            }
        }

        $property = $type
            ->getProperties()
            ->addOrReturnProperty(new Property(
                memberOf: $type,
                sxe: $any,
                sourceFilename: $type->getSourceFilename(),
                name: PHPFHIR_VALUE_PROPERTY_NAME,
                minOccurs: $minOccurs,
                maxOccurs: $maxOccurs,
                namespace: $namespace,
            ));

        // parse through child elements
        foreach ($any->children('xs', true) as $child) {
            switch ($child->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $any, $child);
            }
        }
    }
}