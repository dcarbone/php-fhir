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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Enum\BaseType;
use DCarbone\PHPFHIR\Enum\SimpleType;

/**
 * Class ClassTypeUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ClassTypeUtils
{
    /**
     * @param string|\SimpleXMLElement $input
     * @return SimpleType
     */
    public static function getSimpleClassType($input)
    {
        if ($input instanceof \SimpleXMLElement) {
            $name = XMLUtils::getObjectNameFromElement($input);
        } else {
            $name = $input;
        }

        if (is_string($name)) {
            return new SimpleType(ltrim(strrchr($name, '-'), "-"));
        }

        throw new \InvalidArgumentException('Unable to determine Simple Class Type for "' . (string)$input . '"');
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
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

        $type->setBaseType(new BaseType($baseName));
    }
}