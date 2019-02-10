<?php namespace DCarbone\PHPFHIR\Definition;

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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Enumeration;
use DCarbone\PHPFHIR\Definition\EnumerationValue;
use DCarbone\PHPFHIR\Definition\Property;
use DCarbone\PHPFHIR\Enum\ElementTypeEnum;
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
     * @return \DCarbone\PHPFHIR\Definition\Property
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
                throw new \DomainException(sprintf(
                    'Encountered property on Type "%s" with no "name" or "ref" attribute, cannot create property for it.  Property definition: "%s"',
                    $type,
                    $element->saveXML()
                ));
            }

            $name = $ref;
            $type = $ref;
        }

        $property = new Property($config, $name, $type, $element);

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
    public static function parseEnumerationElement(VersionConfig $config,
                                                   Types $types,
                                                   Type $type,
                                                   \SimpleXMLElement $element)
    {
        // attempt to find the actual value
        $value = $element->attributes()->value;
        if (null === $value) {
            $config->getLogger()->warning(sprintf(
                'Unable to locate "value" attribute on %s element: %s',
                ElementTypeEnum::ENUMERATION,
                $element
            ));
            return;
        }
        if ($type->getEnumeration()->hasRawValue((string)$value)) {
            // since the same type definition can appear multiple times, just return false if
            // this type has already been through the enum parsing stuff once.
            return;
        }
        // create new enum value, attempt to locate "documentation", and add to type
        $enumValue = new EnumerationValue((string)$value, $element);
        $enumValue->setDocumentation(XMLUtils::getDocumentation($element));
        $type->addEnumerationValue($enumValue);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function parseMinLengthElement(VersionConfig $config,
                                                 Types $types,
                                                 Type $type,
                                                 \SimpleXMLElement $element)
    {
        $value = $element->attributes()->value;
        if (null !== $value) {
            $type->setMinlength((int)((string)$value));
        } else {
            $config->getLogger()->warning(sprintf(
                'Unable to locate "value" attribute on %s element: %s',
                ElementTypeEnum::MIN_LENGTH,
                $element
            ));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function parseMaxLengthElement(VersionConfig $config,
                                                 Types $types,
                                                 Type $type,
                                                 \SimpleXMLElement $element)
    {
        $value = $element->attributes()->value;
        if (null !== $value) {
            $type->setMaxLength((int)((string)$value));
        } else {
            $config->getLogger()->warning(sprintf(
                'Unable to locate "value" attribute on %s element: %s',
                ElementTypeEnum::MAX_LENGTH,
                $element
            ));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function parsePatternElement(VersionConfig $config,
                                               Types $types,
                                               Type $type,
                                               \SimpleXMLElement $element)
    {
        $value = $element->attributes()->value;
        if (null !== $value) {
            $type->setPattern((string)$value);
        } else {
            $config->getLogger()->warning(sprintf(
                'Unable to locate "value" attribute on %s element: %s',
                ElementTypeEnum::PATTERN,
                $element
            ));
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
            case ElementTypeEnum::ATTRIBUTE:
                self::parseAttributeElementProperty($config, $types, $type, $element);
                break;
            case ElementTypeEnum::CHOICE:
                self::implementChoiceProperty($config, $types, $type, $element);
                break;
            case ElementTypeEnum::SEQUENCE:
                self::implementPropertySequence($config, $types, $type, $element);
                break;
            case ElementTypeEnum::ELEMENT:
                self::implementElementProperty($config, $types, $type, $element);
                break;

            case ElementTypeEnum::ANNOTATION:
                if (!$type->getDocumentation()) {
                    $type->setDocumentation(XMLUtils::getDocumentation($element));
                }
                break;

            case ElementTypeEnum::UNION:
                // TODO: don't ignore these...
                $config->getLogger()->warning(sprintf(
                    'Ignoring %s element under Type %s...',
                    $element->getName(),
                    $type
                ));
                break;

            case ElementTypeEnum::ENUMERATION:
                self::parseEnumerationElement($config, $types, $type, $element);
                break;

            case ElementTypeEnum::MIN_LENGTH:
                self::parseMinLengthElement($config, $types, $type, $element);
                break;

            case ElementTypeEnum::MAX_LENGTH:
                self::parseMaxLengthElement($config, $types, $type, $element);
                break;

            case ElementTypeEnum::PATTERN:
                self::parsePatternElement($config, $types, $type, $element);
                break;

            case ElementTypeEnum::SIMPLE_TYPE:
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