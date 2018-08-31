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
    public static function buildResourceContainerBody(Config $config, Type $type)
    {
        $out = '';
        foreach ($type->getProperties()->getSortedIterator() as $i => $property) {
            if ($i > 0) {
                $out .= '        elseif ';
            } else {
                $out .= '        if';
            }
            $out .= "(isset(\$this->{$property->getName()})) return \$this->{$property->getName()}\n";
        }
        return $out . "else return null;\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveBody(Config $config, Type $type)
    {
        return <<<PHP
        return \$this->getValue();

PHP;

    }

    public static function buildParentCall(Config $config, Type $type)
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
    public static function buildPrimitiveContainerBody(Config $config, Type $type)
    {
        $out = static::buildParentCall($config, $type);
        $properties = $type->getProperties()->getSortedIterator();
        if ($config->mustMungePrimitives()) {
            // "primitive containers" are things like "string".  Types that extend
            // "Element" but are only used to store a primitive value.  When we see these,
            // and NOTHING but their "value" field is set, only return the value if munging is enabled
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
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildDefaultBody(Config $config, Type $type)
    {
        $out = static::buildParentCall($config, $type);
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
            if ($propType->isPrimitive()) {
                $out .= PropertyUtils::buildPrimitiveJSONMarshalStatement($config, $type, $property);
            }
        }

        return $out . "        return \$a;\n";
    }
}