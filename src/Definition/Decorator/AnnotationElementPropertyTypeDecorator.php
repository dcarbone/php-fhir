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
use DCarbone\PHPFHIR\Enum\ElementName;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\TypeBuilderUtils;

/**
 * Class AnnotationElementTypeDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
abstract class AnnotationElementPropertyTypeDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $annotation
     */
    public static function decorate(
        VersionConfig $config,
        Types $types,
        Type $type,
        Property $property,
        \SimpleXMLElement $annotation
    ): void {
        foreach ($annotation->attributes() as $attribute) {
            switch ($attribute->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $annotation, $attribute);
            }
        }

        foreach ($annotation->children('xs', true) as $child) {
            switch ($child->getName()) {
                case ElementName::DOCUMENTATION->value:
                    TypeBuilderUtils::setPropertyStringFromElementValue(
                        $property,
                        $annotation,
                        $child,
                        'addDocumentationFragment'
                    );
                    break;
                case ElementName::COMPLEX_CONTENT->value:
                    ComplexContentElementTypeDecorator::decorate($config, $types, $type, $child);
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $annotation, $child);
            }
        }
    }
}