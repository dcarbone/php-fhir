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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class XMLSerializeUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class XMLSerializeUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(Config $config, Type $type)
    {
        return PHPFHIR_DEFAULT_XML_SERIALIZE_HEADER;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildSXE(Type $type)
    {
        static $ns = FHIR_XMLNS;
        $name = str_replace(
            NameUtils::$classNameSearch,
            NameUtils::$classNameReplace,
            $type->getFHIRName()
        );
        return <<<PHP
    if (null === \$sxe) {
        \$sxe = new \SimpleXMLElement('<{$name} xmlns="{$ns}"></{$name}>');
    }

PHP;
    }

    /**
     * @return string
     */
    protected static function returnStmt()
    {
        return <<<PHP
    if (\$returnSXE) {
        return \$sxe;
    }
    return \$sxe->saveXML();

PHP;

    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveBody(Config $config, Type $type)
    {
        $out = static::buildSXE($type);
        $out .= <<<PHP
        if (null !== (\$v = \$this->getValue())) {
            \$sxe->addAttribute('value', (string)\$v);
        }

PHP;
        return $out . static::returnStmt();
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveContainerBody(Config $config, Type $type)
    {
        $out = static::buildSXE($type);

        return $out . static::returnStmt();
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param bool $needsParentCall
     * @return string
     */
    protected static function buildDefaultBody(Config $config, Type $type, $needsParentCall)
    {
        $out = static::buildSXE($type);

        return $out . static::returnStmt();
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildBody(Config $config, Type $type)
    {
        if ($type->isPrimitive()) {
            return static::buildPrimitiveBody($config, $type);
        }
        if ($type->hasPrimitiveParent()) {
            return '';
        }
        if ($type->isPrimitiveContainer()) {
            return static::buildPrimitiveContainerBody($config, $type);
        }
        return static::buildDefaultBody($config, $type, true);
    }
}