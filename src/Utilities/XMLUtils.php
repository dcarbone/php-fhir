<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Definition\Type\Property\Enumeration;
use DCarbone\PHPFHIR\Definition\Type\Property\EnumerationValue;

/**
 * Class XMLUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class XMLUtils
{
    /**
     * @param \SimpleXMLElement $extensionElement
     * @return null|string
     */
    public static function getBaseFHIRElementNameFromExtension(\SimpleXMLElement $extensionElement)
    {
        if ('extension' !== $extensionElement->getName()) {
            $xpath = $extensionElement->xpath('xs:complexContent/xs:extension');
            if (0 === count($xpath)) {
                $xpath = $extensionElement->xpath('xs:extension');
            }

            if (0 === count($xpath)) {
                return null;
            }

            $extensionElement = $xpath[0];
        }

        $attributes = $extensionElement->attributes();
        return (string)$attributes['base'];
    }

    /**
     * @param \SimpleXMLElement $restrictionElement
     * @return null|string
     */
    public static function getBaseFHIRElementNameFromRestriction(\SimpleXMLElement $restrictionElement)
    {
        if ('restriction' !== $restrictionElement->getName()) {
            $xpath = $restrictionElement->xpath('xs:complexContent/xs:restriction');
            if (0 === count($xpath)) {
                $xpath = $restrictionElement->xpath('xs:restriction');
            }

            if (0 === count($xpath)) {
                return null;
            }

            $restrictionElement = $xpath[0];
        }

        $attributes = $restrictionElement->attributes();

        if (isset($attributes['base'])) {
            return (string)$attributes['base'];
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $sxe
     * @return null|string
     */
    public static function getObjectNameFromElement(\SimpleXMLElement $sxe)
    {
        $attributes = $sxe->attributes();

        if ($name = $attributes['name']) {
            return (string)$name;
        }

        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|\SimpleXMLElement
     */
    public static function findAnnotationElement(\SimpleXMLElement $parent)
    {
        $annotation = $parent->xpath('xs:annotation');
        if (1 === count($annotation)) {
            return $annotation[0];
        }
        return null;
    }

    /**
     * @param \SimpleXMLElement $annotation
     * @return null|string|array
     */
    public static function getDocumentation(\SimpleXMLElement $annotation)
    {
        if ('annotation' !== $annotation->getName()) {
            $annotation = self::findAnnotationElement($annotation);
            if (null === $annotation) {
                return null;
            }
        }

        $return = [];
        foreach ($annotation->children('xs', true) as $child) {
            if ('documentation' === $child->getName()) {
                $return[] = (string)$child;
            }
        }
        return $return;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|\SimpleXMLElement
     */
    public static function findPatternElement(\SimpleXMLElement $parent)
    {
        $pattern = $parent->xpath('xs:pattern');
        if (1 === count($pattern)) {
            return $pattern[0];
        }
        return null;
    }

    /**
     * @param \SimpleXMLElement $pattern
     * @return null|string
     */
    public static function getPattern(\SimpleXMLElement $pattern)
    {
        if ('pattern' !== $pattern->getName()) {
            $pattern = self::findPatternElement($pattern);
            if (null === $pattern) {
                return null;
            }
        }
        $attributes = $pattern->attributes();
        if (isset($attributes['value'])) {
            return (string)$attributes['value'];
        }
        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return \DCarbone\PHPFHIR\Definition\Type\Property\Enumeration|null
     */
    public static function extractEnumeratedValues(\SimpleXMLElement $parent)
    {
        $enum = new Enumeration();
        foreach ($parent->children('xs', true) as $child) {
            if ('enumeration' === $child->getName()) {
                $attrs = $child->attributes();
                if (isset($attrs['value'])) {
                    $val = new EnumerationValue((string)$attrs['value']);
                    $val->setDocumentation(self::getDocumentation($child));
                    $enum->addValue($val);
                }
            }
        }
        if (0 < count($enum)) {
            return $enum;
        }
        return null;
    }
}