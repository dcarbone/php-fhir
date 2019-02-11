<?php

namespace DCarbone\PHPFHIR\Utilities;

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

use DCarbone\PHPFHIR\Definition\Property;

/**
 * Class PropertyBuilderUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class PropertyBuilderUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     * @param mixed $value
     */
    public static function callSetter(Property $property,
                                      \SimpleXMLElement $parentElement,
                                      \SimpleXMLElement $source,
                                      $setterMethod,
                                      $value)
    {
        if (!method_exists($property, $setterMethod)) {
            throw ExceptionUtils::createPropertySetterMethodNotFoundException(
                $property,
                $parentElement,
                $source,
                $setterMethod
            );
        }
        $property->{$setterMethod}($value);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     */
    public static function setStringFromAttribute(Property $property,
                                                  \SimpleXMLElement $parentElement,
                                                  \SimpleXMLElement $attribute,
                                                  $setterMethod)
    {
        self::callSetter($property, $parentElement, $attribute, $setterMethod, (string)$attribute);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     */
    public static function setStringFromElementAttribute(Property $property,
                                                         \SimpleXMLElement $element,
                                                         $setterMethod,
                                                         $attributeName = 'value')
    {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedPropertyElementAttributeNotFoundException(
                $property,
                $element,
                $attributeName
            );
        }
        self::setStringFromAttribute($property, $element, $attr, $setterMethod);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $parent
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     */
    public static function setStringFromElementValue(Property $property,
                                                     \SimpleXMLElement $parent,
                                                     \SimpleXMLElement $source,
                                                     $setterMethod)
    {
        self::callSetter($property, $parent, $source, $setterMethod, (string)$source);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     */
    public static function setIntegerFromAttribute(Property $property,
                                                   \SimpleXMLElement $parentElement,
                                                   \SimpleXMLElement $attribute,
                                                   $setterMethod)
    {
        $int = (string)$attribute;
        if (!ctype_digit($int)) {
            throw new \DomainException(sprintf(
                'Tried to cast Property "%s" in file "%s" attribute "%s" value "%s" as int',
                $property->getName(),
                $property->getSourceFileBasename(),
                $attribute->getName(),
                $int
            ));
        }
        self::callSetter($property, $parentElement, $attribute, $setterMethod, intval($int, 10));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     */
    public static function setIntegerFromElementAttribute(Property $property,
                                                          \SimpleXMLElement $element,
                                                          $setterMethod,
                                                          $attributeName = 'value')
    {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedPropertyElementAttributeNotFoundException(
                $property,
                $element,
                $attributeName
            );
        }
        self::setIntegerFromAttribute($property, $element, $attr, $setterMethod);
    }
}