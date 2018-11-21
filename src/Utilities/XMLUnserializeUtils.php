<?php

namespace DCarbone\PHPFHIR\Utilities;

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
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class XMLUnserializeUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class XMLUnserializeUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(VersionConfig $config, Type $type)
    {
        $out = <<<PHP
    /**
     * @param \SimpleXMLElement|string|null \$sxe
     * @return null|{$type->getFullyQualifiedClassName(true)}
     */
    public static function xmlUnserialize(\$sxe = null)
    {
        if (null === \$sxe) {
            return null;
        }
        if (is_string(\$sxe)) {
            \$sxe = new \SimpleXMLElement(\$sxe);
        }
        if (!(\$sxe instanceof \SimpleXMLElement)) {
            throw new \InvalidArgumentException('{$type->getClassName()}::fromXML - Argument 1 expected to be XML string or instance of \SimpleXMLElement, '.gettype(\$sxe).' seen.');
        }

PHP;
        return $out;
    }

    public static function buildPrimitiveBody(VersionConfig $config, Type $type)
    {
        $out = <<<PHP
        \$attributes = \$sxe->attributes();
        if (\$attributes && isset(\$attributes['value'])) {
            return new static((string)\$attributes['value']);
        }
        if (
PHP;

    }
}