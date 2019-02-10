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
 * Class ExceptionUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class ExceptionUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $attribute
     * @return \DomainException
     */
    public static function createUnexpectedAttributeException(Type $type,
                                                              \SimpleXMLElement $parentElement,
                                                              \SimpleXMLElement $attribute)
    {
        return new \DomainException(sprintf(
            'Unexpected attribute "%s" on element "%s" in type "%s" defined in file "%s": %s',
            $attribute->getName(),
            $parentElement->getName(),
            $type->getFHIRName(),
            $type->getSourceFileBasename(),
            (string)$attribute
        ));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $element
     * @return \DomainException
     */
    public static function createUnexpectedElementException(Type $type,
                                                            \SimpleXMLElement $parentElement,
                                                            \SimpleXMLElement $element)
    {
        return new \DomainException(sprintf(
            'Unexpected element "%s" under element "%s" found in type "%s" defined in file "%s": %s',
            $element->getName(),
            $parentElement->getName(),
            $type->getFHIRName(),
            $type->getSourceFileBasename(),
            $element->saveXML()
        ));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     * @param $attributeName
     * @return \DomainException
     */
    public static function createExpectedElementAttributeNotFoundException(Type $type,
                                                                           \SimpleXMLElement $element,
                                                                           $attributeName)
    {
        return new \DomainException(sprintf(
            'Expected attribute "%s" not found on element "%s" for type "%s" in file "%s": %s',
            $attributeName,
            $element->getName(),
            $type->getFHIRName(),
            $type->getSourceFileBasename(),
            $element->saveXML()
        ));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parentElement
     * @param \SimpleXMLElement $elementOrAttribute
     * @param string $setterMethod
     * @return \DomainException
     */
    public static function createSetterMethodNotFoundException(Type $type,
                                                               \SimpleXMLElement $parentElement,
                                                               \SimpleXMLElement $elementOrAttribute,
                                                               $setterMethod)
    {
        return new \DomainException(sprintf(
            'Type "%s" from file "%s" missing setter "%s" for "%s" in parent "%s": %s',
            $type->getFHIRName(),
            $type->getSourceFileBasename(),
            $setterMethod,
            $elementOrAttribute->getName(),
            $parentElement->getName(),
            $elementOrAttribute->saveXML()
        ));
    }
}