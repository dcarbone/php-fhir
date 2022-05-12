<?php declare(strict_types=1);

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
use SimpleXMLElement;

/**
 * Class ChoiceElementTypeDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
abstract class ChoiceElementTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $choice
     */
    public static function decorate(VersionConfig $config, Types $types, Type $type, SimpleXMLElement $choice): void
    {
        $minOccurs = null;
        $maxOccurs = null;
        foreach ($choice->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::MIN_OCCURS:
                    $minOccurs = (string)$attribute;
                    break;
                case AttributeNameEnum::MAX_OCCURS:
                    $maxOccurs = (string)$attribute;
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $choice, $attribute);
            }
        }

        $annotationElement = null;
        foreach ($choice->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementNameEnum::ANNOTATION:
                    $annotationElement = $child;
                    break;

                case ElementNameEnum::ELEMENT:
                    ChoiceElementElementPropertyDecorator::decorate(
                        $config,
                        $types,
                        $type,
                        $child,
                        $minOccurs,
                        $maxOccurs,
                        $annotationElement
                    );
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $choice, $child);
            }
        }
    }
}