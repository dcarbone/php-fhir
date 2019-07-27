<?php namespace DCarbone\PHPFHIR\Generator;

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
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * Class TemplateBuilder
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class TemplateBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function generatePHPFHIRTypeInterface(VersionConfig $config, Types $types)
    {
        return require PHPFHIR_TEMPLATE_INTERFACES_DIR . '/phpfhir_type.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function generatePHPFHIRContainedTypeInterface(VersionConfig $config, Types $types)
    {
        return require PHPFHIR_TEMPLATE_INTERFACES_DIR . '/phpfhir_contained_type.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function generateTypeClass(VersionConfig $config, Types $types, Type $type)
    {
        // use raw require at this level
        switch ($typeKind = $type->getKind()) {
            case TypeKindEnum::PRIMITIVE:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/primitive_class.php';
            case TypeKindEnum::PRIMITIVE_CONTAINER:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/primitive_container_class.php';
            case TypeKindEnum::_LIST:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/list_class.php';

//            case TypeKindEnum::RESOURCE_CONTAINER:
//            case TypeKindEnum::RESOURCE_INLINE:
//                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/resource_container_class.php';

            default:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/default_class.php';
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function generateConstants(VersionConfig $config, Types $types)
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . '/constants.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function generateTypeMapClass(VersionConfig $config, Types $types)
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . '/typemap_class.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function generateAutoloaderClass(VersionConfig $config, Types $types)
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . '/autoloader_class.php';
    }
}