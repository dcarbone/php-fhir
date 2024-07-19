<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition\Decorator;

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
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIR\Enum\AttributeName;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/**
 * Class ChoiceElementElementPropertyDecorator
 * @package DCarbone\PHPFHIR\Version\Definition\Decorator
 */
abstract class ChoiceElementElementPropertyDecorator
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string|null $minOccurs
     * @param string|null $maxOccurs
     * @param \SimpleXMLElement|null $annotationElement
     */
    public static function decorate(
        VersionConfig $config,
        Types $types,
        Type $type,
        \SimpleXMLElement $element,
        ?string $minOccurs,
        ?string $maxOccurs,
        \SimpleXMLElement $annotationElement = null
    ): void {
        $property = new Property($type, $element, $type->getSourceFilename());

        if (null !== $minOccurs) {
            $property->setMinOccurs(intval($minOccurs));
        }
        if (null !== $maxOccurs) {
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
            switch ($attribute->getName()) {
                case AttributeName::REF->value:
                    $property->setRef((string)$attribute);
                    break;
                case AttributeName::NAME->value:
                    $property->setName((string)$attribute);
                    break;
                case AttributeName::TYPE->value:
                    $property->setValueFHIRTypeName((string)$attribute);
                    break;
                case AttributeName::MIN_OCCURS->value:
                    $property->setMinOccurs(intval((string)$attribute));
                    break;
                case AttributeName::MAX_OCCURS->value:
                    $property->setMaxOccurs((string)$attribute);
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

        $type->getLocalProperties()->addProperty($property);
    }
}