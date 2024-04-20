<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Definition\Property;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Enum\PrimitiveType;
use DCarbone\PHPFHIR\Enum\TypeKind;

abstract class TypeHintUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveType $primitiveType
     * @param bool $nullable
     * @return string
     */
    public static function primitivePHPValueTypeHint(VersionConfig $config, PrimitiveType $primitiveType, bool $nullable): string
    {
        // this assumes the property's value type is a primiive.
        // it will bomb if not.
        return sprintf(
            '%s%s',
            $nullable ? 'null|' : '',
            $primitiveType->getPHPReturnValueTypeHint()
        );
    }

    public static function primitivePHPReturnValueTypeDoc(VersionConfig $config, PrimitiveType $primitiveType, bool $nullable, bool $asCollection): string
    {
        $hint = $primitiveType->getPHPReturnValueTypeHint();

        if ($asCollection) {
            return sprintf('%s[]', $hint);
        }

        if ($nullable) {
            return sprintf('null|%s', $hint);
        }

        return $hint;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveType $primitiveType
     * @param bool $nullable
     * @param bool $asCollection
     * @return string
     */
    public static function primitivePHPValueTypeSetterDoc(VersionConfig $config, PrimitiveType $primitiveType, bool $nullable, bool $asCollection): string
    {
        $hintTypes = $primitiveType->getPHPReceiveValueTypeHints();

        if ($asCollection) {
            $hintTypes[] = array_map(function(string $v) { return sprintf('%s[]', $v); }, $hintTypes);
        } else if ($nullable) {
            array_unshift($hintTypes, 'null');
        }

        return implode('|', $hintTypes);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function primitiveValuePropertyTypeHint(VersionConfig $config, Property $property, bool $nullable): string
    {
        return self::primitivePHPValueTypeHint($config, $property->getMemberOf()->getPrimitiveType(), $nullable);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param bool $nullable
     * @param bool $asCollection
     * @return string
     */
    public static function primitiveValuePropertyTypeDoc(VersionConfig $config, Property $property, bool $nullable, bool $asCollection): string
    {
        return self::primitivePHPReturnValueTypeDoc($config, $property->getMemberOf()->getPrimitiveType(), $nullable, $asCollection);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param bool $nullable
     * @return string
     */
    public static function typeTypeHint(VersionConfig $config, Type $type, bool $nullable): string
    {
        $tk = $type->getKind();

        // if this is an inline resource
        if ($tk->isOneOf(TypeKind::RESOURCE_INLINE, TypeKind::RESOURCE_CONTAINER)) {
            return sprintf(
                '%s%s',
                $nullable ? 'null|' : '',
                PHPFHIR_INTERFACE_CONTAINED_TYPE
            );
        }

        // if we land here, use the value type's class
        return sprintf(
            '%s%s',
            $nullable ? 'null|' : ':',
            $type->getClassName()
        );
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function typeSetterTypeHint(VersionConfig $config, Type $type, bool $nullable): string
    {
        $tk = $type->getKind();
        $types = $nullable ? ['null'] : [];

        if ($tk === TypeKind::PRIMITIVE_CONTAINER) {
            $pt = $type->getLocalProperties()->getProperty('value')->getValueFHIRType();
            $types = array_merge($types, $pt->getPrimitiveType()->getPHPReceiveValueTypeHints());
            array_push(
                $types,
                $pt->getClassName(),
                $type->getClassName(),
            );
        } else if ($tk->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) {
            $types = array_merge($types, $type->getprimitiveType()->getPHPReceiveValueTypeHints());
        } else {
            $types[] = $type->getClassName();
        }

        return implode('|', array_unique($types));
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param bool $nullable
     * @param bool $asCollection
     * @return string
     */
    public static function typeTypeDoc(VersionConfig $config, Type $type, bool $nullable, bool $asCollection): string
    {
        // if nullable, add to list of additional types
        $types = [];

        // fetch type's kind
        $tk = $type->getKind();

        // if this is an inline resource
        if ($tk->isOneOf(TypeKind::RESOURCE_INLINE, TypeKind::RESOURCE_CONTAINER)) {
            array_push(
                $types,
                sprintf('\\%s\\', trim($config->getNamespace(true), '\\')),
                PHPFHIR_INTERFACE_CONTAINED_TYPE,
            );
        } else {
            $types[] = $type->getFullyQualifiedClassName(true);
        }

        // if this type is a collection, add [] to end of each element
        if ($asCollection) {
            $types = array_map(function (string $n): string {
                return sprintf('%s[]', $n);
            }, $types);
        }

        // if this type is nullable, prepend list with 'null'
        if ($nullable) {
            array_unshift($types, 'null');
        }

        return implode('|', $types);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyTypeHint(VersionConfig $config, Property $property, bool $nullable): string
    {
        // if this proprety is a collection, the type hint must be a potentially nullable array
        if ($property->isCollection()) {
            return sprintf('%sarray', $nullable ? 'null|' : '');
        }

        // first, check to see if there is a FHIR type for this property value
        $t = $property->getValueFHIRType();

        // if null, the (hopefully) only possibility is that this is a value property for a primitive type
        if (null === $t) {
            return self::primitiveValuePropertyTypeHint($config, $property, $nullable);
        }

        // otherwise, hint as the underlying type
        return self::typeTypeHint($config, $t, $nullable);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyGetterTypeDoc(VersionConfig $config, Property $property, bool $nullable): string
    {
        // determine if this property contains a FHIR type or a raw php type
        $t = $property->getValueFHIRType();
        if (null === $t) {
            return self::primitiveValuePropertyTypeDoc($config, $property, $nullable, $property->isCollection());
        }

        return self::typeTypeDoc($config, $t, $nullable, $property->isCollection());
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param bool $asCollection
     * @return string
     */
    public static function propertySetterTypeDoc(VersionConfig $config, Property $property, bool $asCollection): string
    {
        // determine if this property contains a FHIR type or a raw php type
        $pt = $property->getValueFHIRType();
        if (null === $pt) {
            return self::primitiveValuePropertyTypeDoc($config, $property, !$asCollection, $asCollection);
        }

        $ptk = $pt->getKind();

        $hintTypes = ['null'];

        if ($ptk === TypeKind::PRIMITIVE_CONTAINER) {
            $ptp = $pt->getLocalProperties()->getProperty('value')->getValueFHIRType();
            $hintTypes = array_merge($hintTypes, $ptp->getPrimitiveType()->getPHPReceiveValueTypeHints());
            array_push(
                $hintTypes,
                self::typeTypeDoc($config, $ptp, false, $asCollection),
                self::typeTypeDoc($config, $pt, false, $asCollection),
            );
        } else if ($ptk->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) {
            $hintTypes = array_merge($hintTypes, $pt->getPrimitiveType()->getPHPReceiveValueTypeHints());
            $hintTypes[] = self::typeTypeDoc($config, $pt, false, $asCollection);
        } else {
            $hintTypes[] = self::typeTypeDoc($config, $pt, false, $asCollection);
        }

        return implode('|', array_unique($hintTypes));
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @return string
     */
    public static function propertySetterTypeHint(VersionConfig $config, Property $property, bool $nullable): string
    {
        $pt = $property->getValueFHIRType();
        $ptk = $pt->getKind();

        $hint = self::typeSetterTypeHint($config, $pt, $nullable);

        if ($ptk->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) {
            return sprintf('%s|%s', $hint, $pt->getClassName());
        }

        return $hint;
    }
}