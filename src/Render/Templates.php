<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Render;

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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;

/**
 * Class TemplateBuilder
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class Templates
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRTypeInterface(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_INTERFACES_DIR . DIRECTORY_SEPARATOR . 'phpfhir_type.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRCommentContainerInterface(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_INTERFACES_DIR . DIRECTORY_SEPARATOR .'phpfhir_comment_container.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRCommentContainerTrait(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_TRAITS_DIR . DIRECTORY_SEPARATOR . 'phpfhir_comment_container.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRValidationAssertionsTrait(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_TRAITS_DIR . DIRECTORY_SEPARATOR .'phpfhir_validation_assertions.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRChangeTrackingTrait(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_TRAITS_DIR . DIRECTORY_SEPARATOR . 'phpfhir_change_tracking.php';
    }
    
    public static function renderPHPFHIRXMLNamespaceTrait(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_TRAITS_DIR . DIRECTORY_SEPARATOR . 'phpfhir_xml_namespace.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRContainedTypeInterface(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_INTERFACES_DIR . DIRECTORY_SEPARATOR . 'phpfhir_contained_type.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRResponseParserConfigClass(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . DIRECTORY_SEPARATOR .'response_parser_config_class.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderPHPFHIRResponseParserClass(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . DIRECTORY_SEPARATOR .'response_parser_class.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function renderTypeClass(VersionConfig $config, Types $types, Type $type): string
    {
        return require PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'class.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function renderRawTypeClass(VersionConfig $config, Types $types, Type $type): string
    {
        return require PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'xhtml.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderConstants(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . DIRECTORY_SEPARATOR .'constants.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderTypeMapClass(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . DIRECTORY_SEPARATOR . 'typemap_class.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderAutoloaderClass(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_UTILITIES_DIR . DIRECTORY_SEPARATOR . 'autoloader_class.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderConstantsTestClass(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_TESTS_DIR . DIRECTORY_SEPARATOR . 'test_class_constants.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderTypeMapTestClass(VersionConfig $config, Types $types): string
    {
        return require PHPFHIR_TEMPLATE_TESTS_DIR . DIRECTORY_SEPARATOR . 'test_class_type_map.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param string $testType
     * @return string
     */
    public static function renderTypeTestClass(VersionConfig $config, Types $types, Type $type, string $testType): string
    {
        return require PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . DIRECTORY_SEPARATOR . $testType . DIRECTORY_SEPARATOR .'class.php';
    }
}