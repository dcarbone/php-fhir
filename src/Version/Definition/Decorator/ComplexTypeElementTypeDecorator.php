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
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Enum\ElementNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use SimpleXMLElement;

/**
 * Class ComplexTypeElementTypeDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class ComplexTypeElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $complexType
     */
    public static function decorate(Config $config, Types $types, Type $type, SimpleXMLElement $complexType): void
    {
        // parse through attributes
        foreach ($complexType->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::NAME->value:
                    continue 2;
                case AttributeNameEnum::MIXED->value:
                    $type->setMixed(boolval((string)$attribute));
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $complexType, $attribute);
            }
        }

        foreach ($complexType->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementNameEnum::ANNOTATION->value:
                    AnnotationElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::COMPLEX_CONTENT->value:
                    ComplexContentElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::SEQUENCE->value:
                    SequenceElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::ATTRIBUTE->value:
                    AttributeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::SIMPLE_CONTENT->value:
                    SimpleContentElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::CHOICE->value:
                    ChoiceElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $complexType, $child);
            }
        }
    }
}