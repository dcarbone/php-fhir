<?php

namespace DCarbone\PHPFHIR\Builder\Methods;

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
 * Class Constructor
 * @package DCarbone\PHPFHIR\Builder\Methods
 */
abstract class Constructor
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
     * {$type->getClassName()} Constructor
     *
     * @var mixed \$data Value depends upon object being constructed.
     */
    public function __construct(\$data = null)
    {
        if (null === \$data) {
            return;
        }
PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildDefaultBody(VersionConfig $config, Type $type)
    {
        $properties = $type->getProperties();
        $out = '';
        $out .= <<<PHP
        if (is_array(\$data)) {
            unset(\$data['resourceType']);

PHP;
        foreach ($properties->getSortedIterator() as $property) {
            $out .= <<<PHP
            if (isset(\$data['{$property->getName()}'])) {

PHP;

            if ($property->isCollection()) {
                $out .= Setter::buildCollectionSetter($config, $type, $property);
            } else {
                $out .= Setter::buildDefaultSetter($config, $type, $property);
            }
            $out .= "            }\n";
        }
        $out .= <<<PHP
        } else {
            throw new \InvalidArgumentException(
                '{$type->getFullyQualifiedClassName(true)}::__construct - Argument 1 expected to be array or null, '.
                gettype(\$data).
                ' seen.'
            );
        }

PHP;

        if ($type->getParentType()) {
            $out .= "        parent::__construct(\$data);\n";
        }

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveBody(VersionConfig $config, Type $type)
    {
        $out = <<<PHP

        if (is_scalar(\$data)) {
            \$this->setValue(\$data);
        } elseif (is_array(\$data) && isset(\$data['value'])) {
            \$this->setValue(\$data['value']);
        } else {
            throw new \InvalidArgumentException('{$type->getFullyQualifiedClassName(true)}::__construct - Expected either scalar value or array with "value" key, saw '.gettype(\$data));
        }

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveContainerBody(VersionConfig $config, Type $type)
    {
        $out = <<<PHP

        if (is_scalar(\$data)) {
            \$this->setValue(\$data);
            return;
        }

PHP;
        return $out . self::buildDefaultBody($config, $type);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildResourceContainerBody(VersionConfig $config, Type $type)
    {
        return <<<PHP
 elseif (is_array(\$data)) {
            if (isset(\$data[PHPFHIRTypeMap::RESOURCE_TYPE_FIELD])) {
                \$type = \$data[PHPFHIRTypeMap::RESOURCE_TYPE_FIELD];
                \$class = PHPFHIRTypeMap::getClassForType(\$type);
                if (null === \$class) {
                    throw new \RuntimeException(sprintf(
                       'Unable to determine class for type %s',
                       \$data[PHPFHIRTypeMap::RESOURCE_TYPE_FIELD]
                    ));
                }
                \$this->{"set{\$type}"}(new \$class(\$data));
            } else {
                \$key = key(\$data);
                if (!is_string(\$key)) {
                    throw new \InvalidArgumentException(sprintf(
                        '{$type->getFullyQualifiedClassName(true)}::__construct - When \$data is an array, the first key must be a string with a value equal to one of the fields defined in this object or the definition of a contained type with a "resourceType" field with the name of the type.  %s seen',
                        \$key
                    ));
                }
                \$this->{"set{\$key}"}(current(\$data));
            }
        } elseif (is_object(\$data)) {
            \$this->{sprintf('set%s', substr(strrchr(get_class(\$data), 'FHIR'), 4))}(\$data);
        } else {
            throw new \InvalidArgumentException(sprintf(
                '{$type->getFullyQualifiedClassName(true)}::__construct - \$data must be an array, an object, or null.  %s seen.',
                gettype(\$data)
            ));
        }

PHP;
    }
}