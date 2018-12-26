<?php namespace DCarbone\PHPFHIR\Definition;

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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Enum\XSDElementType;
use DCarbone\PHPFHIR\Utilities\XMLUtils;

/**
 * Class PropertyExtractor
 * @package DCarbone\PHPFHIR
 */
abstract class PropertyExtractor
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \SimpleXMLElement $element
     * @param string|null $documentation
     * @param null $pattern
     * @param int|null $maxOccurs
     * @param int|null $minOccurs
     * @return \DCarbone\PHPFHIR\Definition\Type\Property
     */
    protected static function buildTypeProperty(VersionConfig $config,
                                                \SimpleXMLElement $element,
                                                $documentation = null,
                                                $pattern = null,
                                                $maxOccurs = null,
                                                $minOccurs = null)
    {
        $attributes = $element->attributes();
        $name = (string)$attributes['name'];
        $type = (string)$attributes['type'];
        if (0 === strpos($type, 'xs:')) {
            $type = substr($type, 3);
        }
        $ref = (string)$attributes['ref'];

        if ('' === $name) {
            if ('' === $ref) {
                trigger_error(sprintf(
                    'Encountered property on Type "%s" with no "name" or "ref" attribute, cannot create property for it.  Property definition: "%s"',
                    $type,
                    $element->saveXML()
                ));

                return null;
            }

            if (0 === strpos($ref, 'xhtml')) {
                $property = new Property($config, substr($ref, 6), PHPFHIR_TYPE_HTML);
                return $property;
            }

            $name = $ref;
            $type = $ref;
        }

        $property = new Property($config, $name, $type);

        if (null !== $documentation) {
            $property->setDocumentation($documentation);
        } else {
            $property->setDocumentation(XMLUtils::getDocumentation($element));
        }

        if (null !== $pattern) {
            $property->setPattern($pattern);
        } else {
            $property->setPattern(XMLUtils::getPattern($element));
        }
        if (null !== $maxOccurs) {
            $property->setMaxOccurs($maxOccurs);
        } elseif (isset($attributes['maxOccurs'])) {
            $property->setMaxOccurs((string)$attributes['maxOccurs']);
        }
        if (null !== $minOccurs) {
            $property->setMinOccurs($minOccurs);
        } elseif (isset($attributes['minOccurs'])) {
            $property->setMinOccurs((string)$attributes['minOccurs']);
        }

        // TODO: this probably isn't gonna work, revisit...
        if ($enum = XMLUtils::extractEnumeratedValues($element)) {
            $property->setEnumeration($enum);
        }

        return $property;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    protected static function parseAttributeElementProperty(VersionConfig $config,
                                                            Types $types,
                                                            Type $type,
                                                            \SimpleXMLElement $element)
    {
        $property = self::buildTypeProperty($config, $element);
        if ($property) {
            $type->addProperty($property);
        }
    }

    /**
     * TODO: Do better, this is all over the place in XHTML responses...
     *
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $choice
     */
    public static function implementChoiceProperty(VersionConfig $config,
                                                   Types $types,
                                                   Type $type,
                                                   \SimpleXMLElement $choice)
    {
        $attributes = $choice->attributes();
        $minOccurs = isset($attributes['minOccurs']) ? (int)$attributes['minOccurs'] : null;
        $maxOccurs = isset($attributes['maxOccurs']) ? (int)$attributes['maxOccurs'] : null;
        $documentation = XMLUtils::getDocumentation($choice);
        $pattern = XMLUtils::getPattern($choice);

        foreach ($choice->xpath('xs:element') as $element) {
            $property = static::buildTypeProperty($config, $element, $documentation, $pattern, $maxOccurs, $minOccurs);
            if ($property) {
                $type->addProperty($property);
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $sequence
     */
    public static function implementPropertySequence(VersionConfig $config,
                                                     Types $types,
                                                     Type $type,
                                                     \SimpleXMLElement $sequence)
    {
        foreach ($sequence->children('xs', true) as $element) {
            /** @var \SimpleXMLElement $element */
            static::implementTypeProperty($config, $types, $type, $element);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function implementElementProperty(VersionConfig $config,
                                                    Types $types,
                                                    Type $type,
                                                    \SimpleXMLElement $element)
    {
        $property = static::buildTypeProperty($config, $element);
        if ($property) {
            $type->addProperty($property);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function implementTypeProperty(VersionConfig $config,
                                                 Types $types,
                                                 Type $type,
                                                 \SimpleXMLElement $element)
    {
        /** @var \SimpleXMLElement $child */
        switch (strtolower($element->getName())) {
            case XSDElementType::ATTRIBUTE:
                self::parseAttributeElementProperty($config, $types, $type, $element);
                break;
            case XSDElementType::CHOICE:
                self::implementChoiceProperty($config, $types, $type, $element);
                break;
            case XSDElementType::SEQUENCE:
                self::implementPropertySequence($config, $types, $type, $element);
                break;
            case XSDElementType::ELEMENT:
                self::implementElementProperty($config, $types, $type, $element);
                break;

            case XSDElementType::ANNOTATION:
                if (!$type->getDocumentation()) {
                    $type->setDocumentation(XMLUtils::getDocumentation($element));
                }
                break;

            case XSDElementType::UNION:
            case XSDElementType::ENUMERATION:
            case XSDElementType::MIN_LENGTH:
            case XSDElementType::MAX_LENGTH:
            case XSDElementType::PATTERN:
            case XSDElementType::SIMPLE_TYPE:
                // TODO: don't ignore these...
                $config->getLogger()->warning(sprintf(
                    'Ignoring %s element under Type %s...',
                    $element->getName(),
                    $type
                ));
                break;

            default:
                throw new \DomainException(sprintf(
                    'Unexpected Type %s Property element "%s" found: %s',
                    $type,
                    $element->getName(),
                    $element->saveXML()
                ));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $parent
     */
    public static function extractTypeProperties(VersionConfig $config,
                                                 Types $types,
                                                 Type $type,
                                                 \SimpleXMLElement $parent)
    {
        foreach ($parent->children('xs', true) as $element) {
            /** @var \SimpleXMLElement $element */
            static::implementTypeProperty($config, $types, $type, $element);
        }
    }
}