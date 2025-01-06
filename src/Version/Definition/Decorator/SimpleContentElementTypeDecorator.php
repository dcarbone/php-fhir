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
use DCarbone\PHPFHIR\Enum\ElementNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use SimpleXMLElement;

/**
 * Class SimpleContentElementTypeDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class SimpleContentElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $simpleContent
     */
    public static function decorate(Config $config, Types $types, Type $type, SimpleXMLElement $simpleContent): void
    {
        foreach ($simpleContent->attributes() as $attribute) {
            switch ($attribute->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $simpleContent, $attribute);
            }
        }

        foreach ($simpleContent->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementNameEnum::EXTENSION->value:
                    ExtensionElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $simpleContent, $child);
            }
        }
    }
}