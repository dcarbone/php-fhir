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
    public static function decorate(Config            $config,
                                    Types             $types,
                                    Type              $type,
                                    \SimpleXMLElement $attributeElement): void
    {
        $name = '';
        $fhirTypeName = '';
        $use = '';
        $fixed = '';

        // parse through attributes
        foreach ($attributeElement->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::NAME->value:
                    $name = (string)$attribute;
                    break;
                case AttributeNameEnum::TYPE->value:
                    $fhirTypeName = (string)$attribute;
                    break;
                case AttributeNameEnum::USE->value:
                    $use = (string)$attribute;
                    break;
                case AttributeNameEnum::FIXED->value:
                    $fixed = (string)$attribute;
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $attributeElement, $attribute);
            }
        }

        if ('' === $name) {
            throw new \DomainException(sprintf(
                'Unable to determine name of property for type %s: %s',
                $type->getFHIRName(),
                $attributeElement->asXML()
            ));
        }

        // either create new or get existing property definition on type.
        $property = $type
            ->getProperties()
            ->addOrReturnProperty(
                new Property(
                    memberOf: $type,
                    sxe: $attributeElement,
                    sourceFilename: $type->getSourceFilename(),
                    name: $name,
                ),
            );

        if ('' !== $fhirTypeName) {
            $property->setValueFHIRTypeName($fhirTypeName);
        }
        if ('' !== $use) {
            $property->setUse(PropertyUseEnum::from($use));
        }
        if ('' !== $fixed) {
            $property->setFixed($fixed);
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
    }
}