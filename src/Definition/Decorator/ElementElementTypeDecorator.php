<?php

namespace DCarbone\PHPFHIR\Definition\Decorator;

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
use DCarbone\PHPFHIR\Definition\Property;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class ElementElementTypeDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
class ElementElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function decorate(VersionConfig $config, Types $types, Type $type, \SimpleXMLElement $element)
    {
        $property = new Property($config, $element, $type->getSourceFilename());

        // parse through attributes
        foreach ($element->attributes() as $attribute) {
            switch ($attrName = $attribute->getName()) {
                case AttributeNameEnum::NAME:
                case AttributeNameEnum::MIN_OCCURS:
                case AttributeNameEnum::MAX_OCCURS:
                case AttributeNameEnum::REF:
                    $property->{"set{$attrName}"}((string)$attribute);
                    break;
                case AttributeNameEnum::TYPE:
                    $property->setValueFHIRTypeName((string)$attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $element, $attribute);
            }
        }

        // parse through child elements
        foreach ($element->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementTypeEnum::ANNOTATION:
                    AnnotationElementPropertyTypeDecorator::decorate($config, $types, $type, $property, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $element, $child);
            }
        }

        $type->addProperty($property);
    }
}