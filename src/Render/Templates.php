<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Render;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\CoreFile;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;

/**
 * Class TemplateBuilder
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class Templates
{
    public static function renderCoreFile(Config $config, CoreFile $coreFile, array $templateArgs): string
    {
        extract($templateArgs);
        return require $coreFile->getTemplateFile();
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionXHTMLTypeClass(Version $version, Types $types, Type $type): string
    {
        return require PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'class_xhtml.php';
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClass(Version $version, Types $types, Type $type): string
    {
        if ($type->getKind()->isResourceContainer($version)) {
            return require PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'class_resource_container.php';
        }
        return require PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'class_default.php';
    }

//    /**
//     * @param \DCarbone\PHPFHIR\Version $version
//     * @param \DCarbone\PHPFHIR\Version\Definition\Types $types
//     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
//     * @return string
//     */
//    public static function renderVersionTypeClassTest(Version $version, Types $types, Type $type): string
//    {
//        return require PHPFHIR_TEMPLATE_VERSION_TYPE_TESTS_DIR . DIRECTORY_SEPARATOR . $testType->value . DIRECTORY_SEPARATOR . 'class.php';
//    }
}