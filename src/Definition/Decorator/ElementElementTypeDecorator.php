<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition\Decorator;

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
use DCarbone\PHPFHIR\Definition\Property;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeName;
use DCarbone\PHPFHIR\Enum\ElementName;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use SimpleXMLElement;

/**
 * Class ElementElementTypeDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
class ElementElementTypeDecorator
{
    /**
     * This method is intended to be used for <xs:element...> elements that are at the top level of an xsd file
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function rootDecorate(VersionConfig $config, Types $types, Type $type, SimpleXMLElement $element): void
    {
        foreach ($element->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeName::NAME->value:
                    break;
                case AttributeName::TYPE->value:
                    /*
                     * This is to support weird shit like this:
                     *
                     * DSTU1:
                     *  <xs:element name="score" type="xs:decimal">
                     *      ...
                     *  </xs:element>
                     *
                     * R4:
                     *  <xs:element name="Bundle" type="Bundle">
                     *      ...
                     *  </xs:element>
                     *
                     * This seems largely useless in some contexts and needed in others (particularly older versions)
                     */
                    $v = (string)$attribute;
                    if ($type->getFHIRName() === $v) {
                        // if the "type" value is exactly equivalent to the "name" value, just assume
                        // weirdness and move on.
                        break;
                    } elseif (str_starts_with($v, 'xs:')) {
                        break;
                    }
                    $type->setParentTypeName($v);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $element, $attribute);
            }
        }

        foreach ($element->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementName::ANNOTATION->value:
                    AnnotationElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $element, $child);
            }
        }
    }

    /**
     * This method is intended to be used for <xs:element...> elements that are children of other elements
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function decorate(VersionConfig $config, Types $types, Type $type, SimpleXMLElement $element): void
    {
        $property = new Property($type, $element, $type->getSourceFilename());

        // parse through attributes
        foreach ($element->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeName::REF->value:
                    $property->setRef((string)$attribute);
                    break;
                case AttributeName::NAME->value:
                    $property->setName((string)$attribute);
                    break;

                case AttributeName::MIN_OCCURS->value:
                    $property->setMinOccurs(intval((string)$attribute));
                    break;
                case AttributeName::MAX_OCCURS->value:
                    $property->setMaxOccurs((string)$attribute);
                    break;

                case AttributeName::TYPE->value:
                    $property->setValueFHIRTypeName((string)$attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $element, $attribute);
            }
        }

        // parse through child elements
        foreach ($element->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementName::ANNOTATION->value:
                    AnnotationElementPropertyTypeDecorator::decorate($config, $types, $type, $property, $child);
                    break;
                case ElementName::COMPLEX_TYPE->value:
                    ComplexTypeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $element, $child);
            }
        }

        $type->getProperties()->addProperty($property);
    }
}