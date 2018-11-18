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
    protected static function buildResourceContainerBody(Config $config, Type $type)
    {
        if ($config->mustMunge()) {
            $out = '';
        } else {
            $out = static::buildSXE($type);
        }

        foreach ($type->getProperties()->getSortedIterator() as $i => $property) {
            $propName = $property->getName();
            $methodName = 'get' . NameUtils::getPropertyMethodName($propName);
            $out .= '        ';
            if (0 < $i) {
                $out .= '} else';
            }
            $out .= "if (null !== (\$v = \$this->{$methodName}())) {\n";
            if ($config->mustMunge()) {
                $out .= "            return \$v->xmlSerialize(\$returnSXE, \$sxe);\n";
            } else {
                $out .= "            return \$v->xmlSerialize(\$returnSXE, \$sxe->addChild('{$propName}'));\n";
            }
        }

        return $out . <<<PHP
        } elseif (\$returnSXE) {
            return \$sxe;
        } else {
            return \$sxe->saveXML();
        }

PHP;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveBody(Config $config, Type $type)
    {
        static $ns = FHIR_XMLNS;
        $name = str_replace(
            NameUtils::$classNameSearch,
            NameUtils::$classNameReplace,
            $type->getFHIRName()
        );
        $out = <<<PHP
        if (null === \$sxe) {
            // This looks weird, however under normal circumstances this case will never be hit.
            \$sxe = new \SimpleXMLElement("<{$name} xmlns=\"{$ns}\" value=\"".(string)\$this."\">".(string)\$this."</{$name}>");
        } else {
            \$sxe->addAttribute('value', (string)\$this);
        }

PHP;
        if ($type->isPrimitiveContainer() && $type->hasParent()) {
            return $out . "        return parent::xmlSerialize(\$returnSXE, \$sxe);\n";
        }
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
        if ($type->isPrimitive() || $type->isPrimitiveContainer()) {
            return static::buildPrimitiveBody($config, $type);
        } elseif ($type->isResourceContainer()) {
            return static::buildResourceContainerBody($config, $type);
        } else {
            return static::buildDefaultBody($config, $type, true);
        }
    }
}