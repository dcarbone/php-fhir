<?php

namespace DCarbone\PHPFHIR\Utilities\Element;

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
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Enum\ElementTypeEnum;
use DCarbone\PHPFHIR\Utilities\BuilderUtils;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class RestrictionElementUtils
 * @package DCarbone\PHPFHIR\Utilities\Element
 */
abstract class RestrictionElementUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $restriction
     */
    public static function decorateType(VersionConfig $config, Types $types, Type $type, \SimpleXMLElement $restriction)
    {
        foreach ($restriction->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::BASE:
                    BuilderUtils::setStringFromAttribute($type, $restriction, $attribute, 'setRestrictionBaseFHIRName');
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $restriction, $attribute);
            }
        }

        foreach ($restriction->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementTypeEnum::SIMPLE_TYPE:
                    SimpleTypeElementUtils::decorateType($config, $types, $type, $child);
                    break;
                case ElementTypeEnum::PATTERN:
                    BuilderUtils::setStringFromElementAttribute($type, $child, 'setPattern');
                    break;

                case ElementTypeEnum::MIN_LENGTH:
                case ElementTypeEnum::MAX_LENGTH:
                    BuilderUtils::setIntegerFromElementAttribute($type, $child, 'set' . $child->getName());
                    break;

                case ElementTypeEnum::ENUMERATION:
                    BuilderUtils::addEnumeratedValue($type, $restriction, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $restriction, $child);
            }
        }
    }
}