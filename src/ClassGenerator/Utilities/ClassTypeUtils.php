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

use DCarbone\PHPFHIR\ClassGenerator\Enum\BaseObjectTypeEnum;
use DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum;
use DCarbone\PHPFHIR\ClassGenerator\Enum\SimpleClassTypesEnum;

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
        if ($input instanceof \SimpleXMLElement)
            $name = XMLUtils::getObjectNameFromElement($input);
        else
            $name = $input;

        if (is_string($name))
            return new SimpleClassTypesEnum(ltrim(strrchr($name, '-'), "-"));

        throw new \InvalidArgumentException('Unable to determine Simple Class Type for "'.(string)$input.'"');
    }

    /**
     * @param \SimpleXMLElement $sxe
     * @return null|ComplexClassTypesEnum
     */
    public static function getComplexClassType(\SimpleXMLElement $sxe)
    {
        $name = XMLUtils::getObjectNameFromElement($sxe);
        if (false !== strpos($name, '.'))
            return new ComplexClassTypesEnum(ComplexClassTypesEnum::COMPONENT);

        $baseName = XMLUtils::getBaseFHIRElementNameFromExtension($sxe);
        if (null === $baseName)
            return null;

        $baseType = new BaseObjectTypeEnum($baseName);
        switch((string)$baseType)
        {
            case BaseObjectTypeEnum::BACKBONE_ELEMENT:
                return new ComplexClassTypesEnum(ComplexClassTypesEnum::RESOURCE);

            default:
                return new ComplexClassTypesEnum((string)$baseType);
        }
    }
}