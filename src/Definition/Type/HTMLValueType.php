<?php

namespace DCarbone\PHPFHIR\Definition\Type;

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

use DCarbone\PHPFHIR\Definition\Type\AbstractType;
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Definition\Type;

/**
 * This type will be used when an "html" type value is seen.
 *
 * Class HTMLValueType
 * @package DCarbone\PHPFHIR\Definition\Type
 */
class HTMLValueType extends AbstractType
{
    /**
     * @return bool
     */
    public function isUndefined()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isHTML()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isPrimitiveTypeValueType()
    {
        return false;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return \DCarbone\PHPFHIR\Definition\Type|void
     */
    public function addProperty(Property $property)
    {
        throw new \BadMethodCallException(sprintf(
            'Type %s is an HTML type and therefore cannot have property %s',
            $this->getFHIRName(),
            $property->getName()
        ));
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type|void
     */
    public function setParentType(Type $type)
    {
        throw new \BadMethodCallException(sprintf(
            'Type %s is an HTML type and therefore cannot have parent %s',
            $this->getFHIRName(),
            $type->getFHIRName()
        ));
    }

    /**
     * @param string|null $parentTypeName
     * @return \DCarbone\PHPFHIR\Definition\Type|void
     */
    public function setParentTypeName($parentTypeName)
    {
        throw new \BadMethodCallException(sprintf(
            'Type %s is an HTML type and therefore cannot have parent name of %s',
            $this->getFHIRName(),
            $parentTypeName
        ));
    }
}