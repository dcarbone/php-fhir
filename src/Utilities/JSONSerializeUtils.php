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
 * Class JSONSerializeUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class JSONSerializeUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(Config $config, Type $type)
    {
        return PHPFHIR_DEFAULT_JSON_SERIALIZE_HEADER;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildResourceContainerBody(Config $config, Type $type)
    {
        $out = '';
        foreach ($type->getProperties()->getSortedIterator() as $i => $property) {
            $propName = $property->getName();
            $methodName = NameUtils::getPropertyMethodName($propName);
            if ($i > 0) {
                $out .= 'else';
            } else {
                $out .= '        ';
            }
            $out .= <<<PHP
if (null !== (\$v = \$this->{$methodName}())) {
            return \$v;
        }
PHP;

            $out .= "(isset(\$this->{$property->getName()})) {\nreturn \$this->{$property->getName()}\n";
        }
        return $out . "else return null;\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveBody(Config $config, Type $type)
    {
        return <<<PHP
        return \$this->getValue();

PHP;

    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildParentCall(Config $config, Type $type)
    {
        $out = '        $a = ';
        if ($parentType = $type->getParentType()) {
            $out .= "parent::jsonSerialize();\n";
        } else {
            $out .= "[];\n";
        }
        if ($type->isResource()) {
            $out .= <<<PHP
        \$a['resourceType'] = self::FHIR_TYPE_NAME;

PHP;
        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveContainerBody(Config $config, Type $type)
    {
        $out = static::buildParentCall($config, $type);
        if ($config->mustMungePrimitives()) {
            // "primitive containers" are things like "string".  Types that extend
            // "Element" but are only used to store a primitive value.  When we see these,
            // and NOTHING but their "value" field is set, only return the value if munging is enabled
            $properties = $type->getProperties()->getSortedIterator();
            $out .= '        if (0 === count($a) && null !== ($v = $this->getValue())';
            if (1 < count($properties)) {
                foreach ($properties as $i => $property) {
                    $pname = $property->getName();
                    if ('value' !== $pname) {
                        $out .= " &&\n            ";
                        if ($property->isCollection()) {
                            $out .= "0 === count(\$this->{$pname})";
                        } else {
                            $out .= "!isset(\$this->{$pname})";
                        }
                    }
                }
            }
            $out .= ") {\n";
            $out .= "            return \$v->getValue();\n";
            $out .= "        }\n";
        }
        return $out . self::buildDefaultBody($config, $type, false);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param bool $needsParentCall
     * @return string
     */
    protected static function buildDefaultBody(Config $config, Type $type, $needsParentCall)
    {
        if ($needsParentCall) {
            $out = static::buildParentCall($config, $type);
        } else {
            $out = '';
        }
        $properties = $type->getProperties()->getSortedIterator();
        foreach ($properties as $property) {
            $propType = $property->getValueType();
            if (null === $propType) {
                $config->getLogger()->warning(sprintf(
                    'Type %s Property %s has undefined type "%s", skipping json marshal output...',
                    $type,
                    $property,
                    $property->getPHPTypeName()
                ));
                continue;
            }
            $out .= PropertyUtils::buildDefaultJSONMarshalStatement($config, $type, $property);
        }

        // TODO: return null if empty?
        return $out . "        return \$a;\n";
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
        if ($type->isResourceContainer() || $type->isInlineResource()) {
            return static::buildResourceContainerBody($config, $type);
        }
        return static::buildDefaultBody($config, $type, true);
    }
}