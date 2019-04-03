<?php

namespace DCarbone\PHPFHIR\Utilities;

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

/**
 * Class JSONSerializeUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class JSONSerializeUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(VersionConfig $config, Type $type)
    {
        return PHPFHIR_DEFAULT_JSON_SERIALIZE_HEADER;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildResourceContainerBody(VersionConfig $config, Type $type)
    {
        $out = '';
        foreach ($type->getProperties()->getSortedIterator() as $i => $property) {
            $propName = $property->getName();
            $methodName = 'get'.NameUtils::getPropertyMethodName($propName);
            if ($i > 0) {
                $out .= ' elseif ';
            } else {
                $out .= '        if ';
            }
            $out .= <<<PHP
(null !== (\$v = (\$this->{$methodName}()))) {
            return \$v;
        }
PHP;

        }
        return $out . " else {\n            return null;\n        }\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveBody(VersionConfig $config, Type $type)
    {
        return <<<PHP
        return \$this->getValue();

PHP;

    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildParentCall(VersionConfig $config, Type $type)
    {
        $out = '        $a = ';
        if ($parentType = $type->getParentType()) {
            $out .= "parent::jsonSerialize();\n";
        } else {
            $out .= "[];\n";
        }
        if ($type->getKind()->isResource()) {
            $out .= <<<PHP
        \$a['resourceType'] = self::FHIR_TYPE_NAME;

PHP;
        }
        return $out;
    }


    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param bool $needsParentCall
     * @return string
     */
    protected static function buildDefaultBody(VersionConfig $config, Type $type, $needsParentCall)
    {
        if ($needsParentCall) {
            $out = static::buildParentCall($config, $type);
        } else {
            $out = '';
        }
        $properties = $type->getProperties()->getSortedIterator();
        foreach ($properties as $property) {
            $out .= PropertyUtils::buildDefaultJSONMarshalStatement($config, $type, $property);
        }

        // TODO: enable returning null if output is empty
        return $out . "        return \$a;\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildBody(VersionConfig $config, Type $type)
    {
        $typeKind = $type->getKind();
        if ($typeKind->isPrimitive()) {
            return static::buildPrimitiveBody($config, $type);
        }
        if ($type->hasPrimitiveParent()) {
            return '';
        }
        if ($type->isPrimitiveContainer()) {
            return static::buildPrimitiveContainerBody($config, $type);
        }
        if ($typeKind->isResourceContainer() || $typeKind->isInlineResource()) {
            return static::buildResourceContainerBody($config, $type);
        }
        return static::buildDefaultBody($config, $type, true);
    }
}