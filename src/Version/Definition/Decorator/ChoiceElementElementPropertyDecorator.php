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
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class ChoiceElementElementPropertyDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class ChoiceElementElementPropertyDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string $minOccurs
     * @param string $maxOccurs
     * @param \SimpleXMLElement|null $annotationElement
     */
    public static function decorate(Config                 $config,
                                    Types                  $types,
                                    Type                   $type,
                                    \SimpleXMLElement      $element,
                                    string                 $minOccurs = '',
                                    string                 $maxOccurs = '',
                                    null|\SimpleXMLElement $annotationElement = null): void
    {

        $name = '';
        $ref = '';
        $valueFHIRTypeName = '';

        foreach ($element->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeNameEnum::REF->value:
                    $ref = (string)$attribute;
                    break;
                case AttributeNameEnum::NAME->value:
                    $name = (string)$attribute;
                    break;
                case AttributeNameEnum::TYPE->value:
                    $valueFHIRTypeName = (string)$attribute;
                    break;
                case AttributeNameEnum::MIN_OCCURS->value:
                    $minOccurs = (string)$attribute;
                    break;
                case AttributeNameEnum::MAX_OCCURS->value:
                    $maxOccurs = (string)$attribute;
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $element, $attribute);
            }
        }

        $property = $type
            ->getProperties()
            ->addOrReturnProperty(new Property(
                memberOf: $type,
                sxe: $element,
                sourceFilename: $type->getSourceFilename(),
                name: $name,
                ref: $ref,
                minOccurs: $minOccurs,
                maxOccurs: $maxOccurs,
                valueFHIRTypeName: $valueFHIRTypeName,
            ));

        if (null !== $annotationElement) {
            AnnotationElementPropertyTypeDecorator::decorate(
                $config,
                $types,
                $type,
                $property,
                $annotationElement
            );
        }

        foreach ($element->children('xs', true) as $child) {
            switch ($element->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $element, $child);
            }
        }
    }
}