<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

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

use DCarbone\PHPFHIR\ClassGenerator\Enum\SimpleClassTypesEnum;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Type;

/**
 * Class ClassTypeUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassTypeUtils
{
    /**
     * @param string|\SimpleXMLElement $input
     * @return SimpleClassTypesEnum
     */
    public static function getSimpleClassType($input)
    {
        if ($input instanceof \SimpleXMLElement) {
            $name = XMLUtils::getObjectNameFromElement($input);
        } else {
            $name = $input;
        }

        if (is_string($name)) {
            return new SimpleClassTypesEnum(ltrim(strrchr($name, '-'), "-"));
        }

        throw new \InvalidArgumentException('Unable to determine Simple Class Type for "' . (string)$input . '"');
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Type $type
     */
    public static function parseComplexClassType(Config $config, Type $type)
    {
        $sxe = $type->getSourceSXE();
        $name = XMLUtils::getObjectNameFromElement($sxe);
        if (false !== strpos($name, '.')) {
            $type->setComponent(true);
            return;
        }

        $baseName = XMLUtils::getBaseFHIRElementNameFromExtension($sxe);

        if (null === $baseName) {
            $baseName = XMLUtils::getBaseFHIRElementNameFromRestriction($sxe);
            if (null === $baseName) {
                return;
            }
        }

        switch ($baseName) {
            case Type::BASE_TYPE_ELEMENT:
            case Type::BASE_TYPE_BACKBONE_ELEMENT:
            case Type::BASE_TYPE_RESOURCE:
            case Type::BASE_TYPE_DOMAIN_RESOURCE:
            case Type::BASE_TYPE_QUANTITY:
                $type->setBaseType($baseName);
                break;

            default:
                $config->getLogger()->warning(sprintf(
                    'Unknown base type "%s" seen for Type %s',
                    $baseName,
                    $type
                ));
        }
    }
}