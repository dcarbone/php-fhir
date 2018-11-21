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
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Class ConstructorUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ConstructorUtils
{
    const NULL_STMT = <<<PHP
        if (null === \$data) {
            return;
        }
PHP;


    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    protected static function buildDefaultSetter(VersionConfig $config, Type $type, Property $property)
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

        $out = <<<PHP
                \$this->{$method}((\$data['{$property->getName()}'] instanceof {$propertyTypeClassName}) ? \$data['{$property->getName()}'] : new {$propertyTypeClassName}(\$data['{$property->getName()}']));

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    protected static function buildCollectionSetter(VersionConfig $config, Type $type, Property $property)
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
                if (is_array(\$data['{$property->getName()}'])) {
                    foreach(\$data['{$property->getName()}'] as \$i => \$v) {
                        if (null === \$v) {
                            continue;
                        }
                        \$this->{$method}((\$v instanceof {$propertyTypeClassName}) ? \$v : new {$propertyTypeClassName}(\$v));
                    }
                }

PHP;
        return $out;
    }

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

PHP;
        return $out . self::NULL_STMT;
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

PHP;
        foreach ($properties->getSortedIterator() as $property) {
            $out .= <<<PHP
            if (isset(\$data['{$property->getName()}'])) {

PHP;

            if ($property->isCollection()) {
                $out .= static::buildCollectionSetter($config, $type, $property);
            } else {
                $out .= static::buildDefaultSetter($config, $type, $property);
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
            \$key = key(\$data);
            if (!is_string(\$key)) {
                throw new \InvalidArgumentException(sprintf(
                    '{$type->getFullyQualifiedClassName(true)}::__construct - When \$data is an array, the first key must be a string with a value equal to one of the fields defined in this object.  %s seen',
                    \$key
                ));
            }
            \$this->{"set{\$key}"}(current(\$data));
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