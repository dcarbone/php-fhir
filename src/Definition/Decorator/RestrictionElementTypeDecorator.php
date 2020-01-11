<?php

namespace DCarbone\PHPFHIR\Definition\Decorator;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Enum\ElementNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\TypeBuilderUtils;

/**
 * Class RestrictionElementTypeDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
abstract class RestrictionElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $restriction
     */
    public static function decorate(VersionConfig $config, Types $types, Type $type, \SimpleXMLElement $restriction)
    {
        foreach ($restriction->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::BASE:
                    $type->setRestrictionBaseFHIRName((string)$attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $restriction, $attribute);
            }
        }

        foreach ($restriction->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementNameEnum::SIMPLE_TYPE:
                    SimpleTypeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::PATTERN:
                    TypeBuilderUtils::setTypeStringFromElementAttribute($type, $child, 'setPattern');
                    break;
                case ElementNameEnum::MIN_LENGTH:
                case ElementNameEnum::MAX_LENGTH:
                    TypeBuilderUtils::setTypeIntegerFromElementAttribute($type, $child, 'set' . $child->getName());
                    break;
                case ElementNameEnum::ENUMERATION:
                    TypeBuilderUtils::addTypeEnumeratedValue($type, $restriction, $child);
                    break;
                case ElementNameEnum::SEQUENCE:
                    SequenceElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::ATTRIBUTE:
                    AttributeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $restriction, $child);
            }
        }
    }
}