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
use DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Enum\ElementNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use SimpleXMLElement;

/**
 * Class RestrictionElementTypeDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class RestrictionElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $restriction
     */
    public static function decorate(Config $config, Types $types, Type $type, SimpleXMLElement $restriction): void
    {
        foreach ($restriction->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::BASE->value:
                    $type->setRestrictionBaseFHIRName((string)$attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $restriction, $attribute);
            }
        }

        foreach ($restriction->children('xs', true) as $child) {
            $attrs = $child->attributes();
            switch ($child->getName()) {
                case ElementNameEnum::SIMPLE_TYPE->value:
                    SimpleTypeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::PATTERN->value:
                    if (isset($attrs[AttributeNameEnum::VALUE->value])) {
                        $type->setpattern((string)$attrs[AttributeNameEnum::VALUE->value]);
                    } else if ('' !== ($v = (string)$child)) {
                        $type->setpattern($v);
                    }
                    break;
                case ElementNameEnum::MIN_LENGTH->value:
                    if (isset($attrs[AttributeNameEnum::VALUE->value])) {
                        $type->setMinLength(intval((string)$attrs[AttributeNameEnum::VALUE->value]));
                    } else if ('' !== ($v = (string)$child)) {
                        $type->setMinLength(intval($v));
                    }
                    break;
                case ElementNameEnum::MAX_LENGTH->value:
                    if (isset($attrs[AttributeNameEnum::VALUE->value])) {
                        $type->setMaxLength(intval((string)$attrs[AttributeNameEnum::VALUE->value]));
                    } else if ('' !== ($v = (string)$child)) {
                        $type->setMaxLength(intval($v));
                    }
                    break;
                case ElementNameEnum::ENUMERATION->value:
                    if (isset($attrs[AttributeNameEnum::VALUE->value])) {
                        $type->addEnumerationValue(new EnumerationValue((string)$attrs[AttributeNameEnum::VALUE->value], $attrs[AttributeNameEnum::VALUE->value]));
                    } else if ('' !== ($v = (string)$child)) {
                        $type->addEnumerationValue(new EnumerationValue($v, $child));
                    }
                    break;
                case ElementNameEnum::SEQUENCE->value:
                    SequenceElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::ATTRIBUTE->value:
                    AttributeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $restriction, $child);
            }
        }
    }
}