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
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class Setter
 * @package DCarbone\PHPFHIR\Builder\Methods
 */
abstract class Setter
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function buildDefaultSetter(VersionConfig $config, Type $type, Property $property)
    {
        $name = $property->getName();
        $method = 'set' . ucfirst($name);
        $propertyName = $property->getName();
        $propertyType = $property->getValueType();
        if (null === $propertyType) {
            // TODO: this is really hacky...
            if ('html' === $property->getFHIRTypeName()) {
                $out = <<<PHP
            \$this->{$method}((string)\$data['{$propertyName}']);

PHP;
            } else {
                throw new \RuntimeException(sprintf(
                    'Cannot create setter for type %s property %s as it defines an unknown type %s',
                    $type->getFHIRName(),
                    $property->getName(),
                    $property->getFHIRTypeName()
                ));
            }
        } else {
            $propertyTypeClassName = $property->getValueType()->getClassName();

            $out = <<<PHP
                \$this->{$method}((\$data['{$propertyName}'] instanceof {$propertyTypeClassName}) ? \$data['{$propertyName}'] : new {$propertyTypeClassName}(\$data['{$propertyName}']));

PHP;
        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function buildCollectionSetter(VersionConfig $config, Type $type, Property $property)
    {
        $name = $property->getName();
        $method = 'add' . ucfirst($name);
        $propertyType = $property->getValueType();
        $propertyName = $property->getName();
        if (null === $propertyType) {
            throw new \RuntimeException(sprintf(
                'Cannot create setter for type %s property %s as it defines an unknown type %s',
                $type->getFHIRName(),
                $property->getName(),
                $property->getFHIRTypeName()
            ));
        }
        $propertyTypeClassName = $property->getValueType()->getClassName();
        $out = <<<PHP
                if (is_array(\$data['{$propertyName}'])) {
                    foreach(\$data['{$propertyName}'] as \$i => \$v) {
                        if (null === \$v) {
                            continue;
                        }
                        \$this->{$method}((\$v instanceof {$propertyTypeClassName}) ? \$v : new {$propertyTypeClassName}(\$v));
                    }
                }

PHP;
        return $out;
    }
}