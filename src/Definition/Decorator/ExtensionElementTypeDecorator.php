<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition\Decorator;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use SimpleXMLElement;

/**
 * Class ExtensionElementTypeDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
abstract class ExtensionElementTypeDecorator
{
    /**
     * The "base" attribute on an extension element defines inheritance
     *
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $attribute
     */
    protected static function parseBaseAttribute(Type $type, SimpleXMLElement $attribute): void
    {
        $parentName = (string)$attribute;
        if ($type->getFHIRName() === $parentName) {
            throw ExceptionUtils::createExtendingSelfException($type, $parentName);
        }
        $type->setParentTypeName($parentName);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $extension
     */
    public static function decorate(VersionConfig $config, Types $types, Type $type, SimpleXMLElement $extension): void
    {
        foreach ($extension->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::BASE:
                    self::parseBaseAttribute($type, $attribute);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $extension, $attribute);
            }
        }

        foreach ($extension->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementNameEnum::ATTRIBUTE:
                    AttributeElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;
                case ElementNameEnum::SEQUENCE:
                    SequenceElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $extension, $child);
            }
        }
    }
}