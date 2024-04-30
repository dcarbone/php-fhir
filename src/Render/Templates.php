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
use DCarbone\PHPFHIR\Enum\TestType;

/**
 * Class TemplateBuilder
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class Templates
{
    /**
     * @param string $coreFilename
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @return string
     */
    public static function renderCoreType(string $coreFilename, VersionConfig $config, Types $types): string
    {
        return require $coreFilename;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function renderXhtmlTypeClass(VersionConfig $config, Types $types, Type $type): string
    {
        return require PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'class_xhtml.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function renderFhirTypeClass(VersionConfig $config, Types $types, Type $type): string
    {
        return require PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'class_default.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Enum\TestType $testType
     * @return string
     */
    public static function renderFhirTypeClassTest(VersionConfig $config, Types $types, Type $type, TestType $testType): string
    {
        return require PHPFHIR_TEMPLATE_TYPE_TESTS_DIR . DIRECTORY_SEPARATOR . $testType->value . DIRECTORY_SEPARATOR .'class.php';
    }
}