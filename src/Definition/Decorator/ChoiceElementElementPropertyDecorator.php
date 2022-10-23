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
use DCarbone\PHPFHIR\Definition\Property;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeNameEnum;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class ChoiceElementElementPropertyDecorator
 * @package DCarbone\PHPFHIR\Definition\Decorator
 */
abstract class ChoiceElementElementPropertyDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param int|null $minOccurs
     * @param int|null $maxOccurs
     * @param \SimpleXMLElement|null $annotationElement
     */
    public static function decorate(
        VersionConfig $config,
        Types $types,
        Type $type,
        \SimpleXMLElement $element,
        ?int $minOccurs,
        ?int $maxOccurs,
        \SimpleXMLElement $annotationElement = null
    ): void {
        $properties = $type->getProperties();
        $property = new Property($type, $element, $type->getSourceFilename());

        if (is_int($minOccurs)) {
            $property->setMinOccurs($minOccurs);
        }
        if (is_int($maxOccurs)) {
            $property->setMaxOccurs($maxOccurs);
        }
        if (null !== $annotationElement) {
            AnnotationElementPropertyTypeDecorator::decorate(
                $config,
                $types,
                $type,
                $property,
                $annotationElement
            );
        }

        foreach ($element->attributes() as $attribute) {
            switch ($atrrName = $attribute->getName()) {
                case AttributeNameEnum::REF:
                    $property->setRef((string)$attribute);
                    break;
                case AttributeNameEnum::NAME:
                    $property->setName((string)$attribute);
                    break;
                case AttributeNameEnum::TYPE:
                    $property->setValueFHIRTypeName((string)$attribute);
                    break;
                case AttributeNameEnum::MIN_OCCURS:
                case AttributeNameEnum::MAX_OCCURS:
                    $property->{"set${atrrName}"}(intval((string)$attribute));
                    break;

                default:
                    throw ExceptionUtils::createUnexpectedAttributeException($type, $element, $attribute);
            }
        }

        if (null === $property->getName()) {
            if ('' === ($ref = $property->getRef())) {
                throw new \DomainException(
                    sprintf(
                        'Unable to determine Property name for Type "%s" in file "%s": %s',
                        $type->getFHIRName(),
                        $type->getSourceFileBasename(),
                        $element->saveXML()
                    )
                );
            }
            $config->getLogger()->notice(
                sprintf(
                    'No "name" field found on element, using "ref" value for Type "%s" Property name: %s',
                    $type->getFHIRName(),
                    $element->saveXML()
                )
            );
            $property->setName($ref);
        }

        foreach ($element->children('xs', true) as $child) {
            switch ($element->getName()) {
                default:
                    throw ExceptionUtils::createUnexpectedElementException($type, $element, $child);
            }
        }

        $properties->addProperty($property);
    }
}