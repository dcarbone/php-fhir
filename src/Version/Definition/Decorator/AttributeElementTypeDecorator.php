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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Enum\ElementNameEnum;
use DCarbone\PHPFHIR\Enum\PropertyUseEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class AttributeElementTypeDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class AttributeElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $attributeElement
     */
    public static function decorate(
        Config $config,
        Types $types,
        Type $type,
        \SimpleXMLElement $attributeElement
    ): void {
        // create property object
        $property = new Property($type, $attributeElement, $type->getSourceFilename());

        // parse through attributes
        foreach ($attributeElement->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::NAME->value:
                    $property->setName((string)$attribute);
                    break;
                case AttributeNameEnum::TYPE->value:
                    $property->setValueFHIRTypeName((string)$attribute);
                    break;
                case AttributeNameEnum::USE->value:
                    $property->setUse(PropertyUseEnum::from((string)$attribute));
                    break;
                case AttributeNameEnum::FIXED->value:
                    $property->setFixed((string)$attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $attributeElement, $attribute);
            }
        }

        // parse through child elements
        foreach ($attributeElement->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementNameEnum::SIMPLE_TYPE->value:
                    SimpleTypeElementPropertyDecorator::decorate($config, $types, $type, $property, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $attributeElement, $child);
            }
        }

        // add property to type
        $type->getLocalProperties()->addProperty($property);
    }
}