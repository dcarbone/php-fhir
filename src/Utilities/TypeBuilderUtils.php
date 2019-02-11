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

use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class BuilderUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class TypeBuilderUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     * @param mixed $value
     */
    public static function callSetter(Type $type,
                                      \SimpleXMLElement $parentElement,
                                      \SimpleXMLElement $source,
                                      $setterMethod,
                                      $value)
    {
        if (!method_exists($type, $setterMethod)) {
            throw ExceptionUtils::createTypeSetterMethodNotFoundException($type, $parentElement, $source, $setterMethod);
        }
        $type->{$setterMethod}($value);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     */
    public static function setStringFromAttribute(Type $type,
                                                  \SimpleXMLElement $parentElement,
                                                  \SimpleXMLElement $attribute,
                                                  $setterMethod)
    {
        self::callSetter($type, $parentElement, $attribute, $setterMethod, (string)$attribute);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     */
    public static function setStringFromElementAttribute(Type $type,
                                                         \SimpleXMLElement $element,
                                                         $setterMethod,
                                                         $attributeName = 'value')
    {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $element, $attributeName);
        }
        self::setStringFromAttribute($type, $element, $attr, $setterMethod);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parent
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     */
    public static function setStringFromElementValue(Type $type,
                                                     \SimpleXMLElement $parent,
                                                     \SimpleXMLElement $source,
                                                     $setterMethod)
    {
        self::callSetter($type, $parent, $source, $setterMethod, (string)$source);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     * @param string $delimiter
     */
    public static function setArrayFromAttribute(Type $type,
                                                 \SimpleXMLElement $parentElement,
                                                 \SimpleXMLElement $attribute,
                                                 $setterMethod,
                                                 $delimiter = ' ')
    {
        self::callSetter(
            $type,
            $parentElement,
            $attribute,
            $setterMethod,
            array_filter(array_map('trim', explode($delimiter, (string)$attribute)))
        );
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     * @param string $delimiter
     */
    public static function setArrayFromElementAttribute(Type $type,
                                                        \SimpleXMLElement $element,
                                                        $setterMethod,
                                                        $attributeName = 'value',
                                                        $delimiter = '')
    {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $element, $attributeName);
        }
        self::setArrayFromAttribute($type, $element, $attr, $setterMethod, $delimiter);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     */
    public static function setIntegerFromAttribute(Type $type,
                                                   \SimpleXMLElement $parentElement,
                                                   \SimpleXMLElement $attribute,
                                                   $setterMethod)
    {
        $int = (string)$attribute;
        if (!ctype_digit($int)) {
            throw new \DomainException(sprintf(
                'Tried to cast Type "%s" in file "%s" attribute "%s" value "%s" as int',
                $type->getFHIRName(),
                $type->getSourceFileBasename(),
                $attribute->getName(),
                $int
            ));
        }
        self::callSetter($type, $parentElement, $attribute, $setterMethod, intval($int, 10));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     */
    public static function setIntegerFromElementAttribute(Type $type,
                                                          \SimpleXMLElement $element,
                                                          $setterMethod,
                                                          $attributeName = 'value')
    {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $element, $attributeName);
        }
        self::setIntegerFromAttribute($type, $element, $attr, $setterMethod);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $enum
     */
    public static function addEnumeratedValue(Type $type, \SimpleXMLElement $parentElement, \SimpleXMLElement $enum)
    {
        $value = $enum->attributes()->value;
        if (null === $value) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $enum, 'value');
        }
        self::callSetter(
            $type,
            $parentElement,
            $enum,
            'addEnumerationValue',
            new \DCarbone\PHPFHIR\Definition\EnumerationValue((string)$value, $enum)
        );
    }
}