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
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\CoreFile $coreFile
     * @param array $kwargs
     * @return string
     */
    public static function renderCoreFile(Config $config, CoreFile $coreFile, array $kwargs): string
    {
        extract($kwargs);
        return require $coreFile->getTemplateFile();
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionXHTMLTypeClass(Version $version, Type $type): string
    {
        return require sprintf('%s/class_xhtml.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClass(Version $version, Type $type): string
    {
        if ($type->getKind()->isResourceContainer($version)) {
            return require sprintf('%s/class_resource_container.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR);
        }
        return require sprintf('%s/class_default.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClassTest(Version $version, Type $type): string
    {
        return require sprintf('%s/class.php', PHPFHIR_TEMPLATE_TESTS_VERSIONS_TYPES_DIR);
    }
}