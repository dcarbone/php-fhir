<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

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

use DCarbone\PHPFHIR\Definition\EnumerationValue;
use DCarbone\PHPFHIR\Definition\Property;
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
    public static function callTypeSetter(
        Type $type,
        \SimpleXMLElement $parentElement,
        \SimpleXMLElement $source,
        string $setterMethod,
        mixed $value
    ): void {
        if (!method_exists($type, $setterMethod)) {
            throw ExceptionUtils::createTypeSetterMethodNotFoundException(
                $type,
                $parentElement,
                $source,
                $setterMethod
            );
        }
        $type->{$setterMethod}($value);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     */
    public static function setTypeStringFromAttribute(
        Type $type,
        \SimpleXMLElement $parentElement,
        \SimpleXMLElement $attribute,
        string $setterMethod
    ): void {
        self::callTypeSetter($type, $parentElement, $attribute, $setterMethod, (string)$attribute);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     */
    public static function setTypeStringFromElementAttribute(
        Type $type,
        \SimpleXMLElement $element,
        string $setterMethod,
        string $attributeName = 'value'
    ): void {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $element, $attributeName);
        }
        self::setTypeStringFromAttribute($type, $element, $attr, $setterMethod);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parent
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     */
    public static function setTypeStringFromElementValue(
        Type $type,
        \SimpleXMLElement $parent,
        \SimpleXMLElement $source,
        string $setterMethod
    ): void {
        self::callTypeSetter($type, $parent, $source, $setterMethod, (string)$source);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     * @param string $delimiter
     */
    public static function setTypeArrayFromAttribute(
        Type $type,
        \SimpleXMLElement $parentElement,
        \SimpleXMLElement $attribute,
        string $setterMethod,
        string $delimiter = ' '
    ): void {
        self::callTypeSetter(
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
    public static function setTypeArrayFromElementAttribute(
        Type $type,
        \SimpleXMLElement $element,
        string $setterMethod,
        string $attributeName = 'value',
        string $delimiter = ''
    ): void {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $element, $attributeName);
        }
        self::setTypeArrayFromAttribute($type, $element, $attr, $setterMethod, $delimiter);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @param string $setterMethod
     */
    public static function setTypeIntegerFromAttribute(
        Type $type,
        \SimpleXMLElement $parentElement,
        \SimpleXMLElement $attribute,
        string $setterMethod
    ): void {
        $int = (string)$attribute;
        if (!ctype_digit($int)) {
            throw new \DomainException(
                sprintf(
                    'Tried to cast Type "%s" in file "%s" attribute "%s" value "%s" as int',
                    $type->getFHIRName(),
                    $type->getSourceFileBasename(),
                    $attribute->getName(),
                    $int
                )
            );
        }
        self::callTypeSetter($type, $parentElement, $attribute, $setterMethod, intval($int));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param string $setterMethod
     * @param string $attributeName
     */
    public static function setTypeIntegerFromElementAttribute(
        Type $type,
        \SimpleXMLElement $element,
        string $setterMethod,
        string $attributeName = 'value'
    ): void {
        $attr = $element->attributes()->{$attributeName};
        if (null === $attr) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $element, $attributeName);
        }
        self::setTypeIntegerFromAttribute($type, $element, $attr, $setterMethod);
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $enumElement
     */
    public static function addTypeEnumeratedValue(Type $type, \SimpleXMLElement $parentElement, \SimpleXMLElement $enumElement): void
    {
        $value = $enumElement->attributes()->value;
        if (null === $value) {
            throw ExceptionUtils::createExpectedTypeElementAttributeNotFoundException($type, $enumElement, 'value');
        }
        $type->addEnumerationValue(new EnumerationValue((string)$value, $enumElement));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     * @param mixed $value
     */
    public static function callPropertySetter(
        Property $property,
        \SimpleXMLElement $parentElement,
        \SimpleXMLElement $source,
        string $setterMethod,
        mixed $value
    ): void {
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
     * @param \SimpleXMLElement $parent
     * @param \SimpleXMLElement $source
     * @param string $setterMethod
     */
    public static function setPropertyStringFromElementValue(
        Property $property,
        \SimpleXMLElement $parent,
        \SimpleXMLElement $source,
        string $setterMethod
    ): void {
        self::callPropertySetter($property, $parent, $source, $setterMethod, (string)$source);
    }
}