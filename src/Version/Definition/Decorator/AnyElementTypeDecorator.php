<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition\Decorator;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeName;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class AnyElementTypeDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class AnyElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $any
     */
    public static function decorate(VersionConfig $config, Types $types, Type $type, \SimpleXMLElement $any): void
    {
        $property = new Property($type, $any, $type->getSourceFilename());

        $property->setValueFHIRTypeName('string-primitive');

        // parse through attributes
        foreach ($any->attributes() as $attribute) {
            switch ($attrName = $attribute->getName()) {
                case AttributeName::NAMESPACE->value:
                    $property->setNamespace((string)$attribute);
                    break;
                case AttributeName::MIN_OCCURS->value:
                    $property->setMinOccurs(intval((string)$attribute));
                    break;
                case AttributeName::MAX_OCCURS->value:
                    $property->setMaxOccurs((string)$attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $any, $attribute);
            }
        }

        // parse through child elements
        foreach ($any->children('xs', true) as $child) {
            switch ($child->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $any, $child);
            }
        }
    }
}