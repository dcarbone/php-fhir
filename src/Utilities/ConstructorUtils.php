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
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Class ConstructorUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ConstructorUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    protected static function buildDefaultSetter(Config $config, Type $type, Property $property)
    {
        $name = $property->getName();
        $method = 'set' . ucfirst($name);
        $propertyType = $property->getValueType();
        if (null === $propertyType) {
            $config->getLogger()->error(sprintf(
                'Cannot create setter for type %s property %s as it defines an unknown type %s',
                $type->getFHIRName(),
                $property->getName(),
                $property->getFHIRTypeName()
            ));
            return '';
        }
        $propertyTypeClassName = $property->getValueType()->getClassName();
        $typeFQN = $property->getValueType()->getFullyQualifiedClassName(true);

        if ($propertyType->isPrimitive() || $propertyType->isPrimitiveContainer()) {
            $out = <<<PHP
                \$this->{$method}(\$value);

PHP;

        } else {
            $out = <<<PHP
                if (is_array(\$value)) {
                    \$value = new {$propertyTypeClassName}(\$value);
                }
                if (!(\$value instanceof {$propertyTypeClassName})) {
                    throw new \InvalidArgumentException("{$type->getFullyQualifiedClassName(true)}::__construct - Property \"{$name}\" must either be instance of {$typeFQN} or data to construct type, saw ".gettype(\$value)); 
                }
                \$this->{$method}(\$value);

PHP;
        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    protected static function buildCollectionSetter(Config $config, Type $type, Property $property)
    {
        $name = $property->getName();
        $method = 'add' . ucfirst($name);
        $propertyType = $property->getValueType();
        if (null === $propertyType) {
            $config->getLogger()->error(sprintf(
                'Cannot create setter for type %s property %s as it defines an unknown type %s',
                $type->getFHIRName(),
                $property->getName(),
                $property->getFHIRTypeName()
            ));
            return '';
        }
        $propertyTypeClassName = $property->getValueType()->getClassName();

        $out = <<<PHP
                if (is_array(\$value)) {
                    foreach(\$value as \$i => \$v) {
                        if (null === \$v) {
                            continue;
                        }
                        if (!(\$v instanceof {$propertyTypeClassName})) {
                            \$this->{$method}(new {$propertyTypeClassName}(\$v));
                        }
                        \$this->{$method}(\$v);
                    }
                }

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(Config $config, Type $type)
    {
        $out = <<<PHP
    /**
     * {$type->getClassName()} Constructor
     *
     * @var mixed \$data Value depends upon object being constructed.
     */
    public function __construct(\$data = null)
    {

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildDefaultBody(Config $config, Type $type)
    {
        $properties = $type->getProperties();
        $out = '';
        $out .= <<<PHP
        if (is_array(\$data)) {

PHP;
        foreach ($properties->getSortedIterator() as $property) {
            $out .= <<<PHP
            if (isset(\$data['{$property->getName()}'])) {
                \$value = \$data['{$property->getName()}'];

PHP;

            if ($property->isCollection()) {
                $out .= static::buildCollectionSetter($config, $type, $property);
            } else {
                $out .= static::buildDefaultSetter($config, $type, $property);
            }
            $out .= "            }\n";
        }
        $out .= <<<PHP
        } else if (null !== \$data) {
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveBody(Config $config, Type $type)
    {
        $out = <<<PHP
        if (is_scalar(\$data)) {
            \$this->setValue(\$data);
        } elseif (is_array(\$data) && isset(\$data['value'])) {
            \$this->setValue(\$data['value']);
        } elseif (null !== \$data) {
            throw new \InvalidArgumentException('{$type->getFullyQualifiedClassName(true)}::__construct - Expected either scalar value or array with "value" key, saw '.gettype(\$data));
        }

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveContainerBody(Config $config, Type $type)
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
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildResourceContainerBody(Config $config, Type $type)
    {
        return <<<PHP
        if (is_array(\$data)) {
            \$key = key(\$data);
            if (!is_string(\$key)) {
                throw new \InvalidArgumentException(sprintf(
                    '{$type->getFullyQualifiedClassName(true)}::__construct - When \$data is an array, the first key must be a string with a value equal to one of the fields defined in this object.  %s seen',
                    \$key
                ));
            }
            \$this->{"set{\$key}"}(current(\$data));
        } else if (is_object(\$data)) {
            \$this->{sprintf('set%s', substr(strrchr(get_class(\$data), 'FHIR'), 4))}(\$data);
        } else if (null !== \$data) {
            throw new \InvalidArgumentException(sprintf(
                '{$type->getFullyQualifiedClassName(true)}::__construct - \$data must be an array, an object, or null.  %s seen.',
                gettype(\$data)
            ));
        }

PHP;
    }
}