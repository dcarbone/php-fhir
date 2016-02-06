<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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
        if ('extension' !== $extensionElement->getName())
        {
            $xpath = $extensionElement->xpath('xs:complexContent/xs:extension');
            if (0 === count($xpath))
                $xpath = $extensionElement->xpath('xs:extension');

            if (0 === count($xpath))
                return null;

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
        if ('restriction' !== $restrictionElement->getName())
        {
            $xpath = $restrictionElement->xpath('xs:complexContent/xs:restriction');
            if (0 === count($xpath))
                $xpath = $restrictionElement->xpath('xs:restriction');

            if (0 === count($xpath))
                return null;

            $restrictionElement = $xpath[0];
        }

        $attributes = $restrictionElement->attributes();

        if (isset($attributes['base']))
            return (string)$attributes['base'];

        return null;
    }

    /**
     * @param \SimpleXMLElement $sxe
     * @return null|string
     */
    public static function getObjectNameFromElement(\SimpleXMLElement $sxe)
    {
        $attributes = $sxe->attributes();

        if ($name = $attributes['name'])
            return (string)$name;

        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|\SimpleXMLElement
     */
    public static function getAnnotationElement(\SimpleXMLElement $parent)
    {
        $annotation = $parent->xpath('xs:annotation');
        if (1 === count($annotation))
            return $annotation[0];

        return null;
    }

    /**
     * @param \SimpleXMLElement $annotation
     * @return null|string|array
     */
    public static function getDocumentation(\SimpleXMLElement $annotation)
    {
        if ('annotation' !== $annotation->getName())
            $annotation = self::getAnnotationElement($annotation);

        if (null === $annotation)
            return null;

        $documentation = $annotation->xpath('xs:documentation');

        if (0 === count($documentation))
            return null;

        $return = array();
        foreach($documentation as $element)
        {
            $return[] = (string)$element;
        }
        return $return;
    }
}