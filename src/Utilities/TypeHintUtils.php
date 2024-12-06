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

use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Enum\PrimitiveType;
use DCarbone\PHPFHIR\Enum\TypeKind;

abstract class TypeHintUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveType $primitiveType
     * @param bool $nullable
     * @return string
     */
    public static function primitivePHPValueTypeHint(Version $version, PrimitiveType $primitiveType, bool $nullable): string
    {
        // this assumes the property's value type is a primiive.
        // it will bomb if not.
        return sprintf(
            '%s%s',
            $nullable ? 'null|' : '',
            $primitiveType->getPHPReturnValueTypeHint()
        );
    }

    public static function primitivePHPReturnValueTypeDoc(Version $version, PrimitiveType $primitiveType, bool $nullable, bool $asCollection): string
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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveType $primitiveType
     * @param bool $nullable
     * @param bool $asCollection
     * @return string
     */
    public static function primitivePHPValueTypeSetterDoc(Version $version, PrimitiveType $primitiveType, bool $nullable, bool $asCollection): string
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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function primitiveValuePropertyTypeHint(Version $version, Property $property, bool $nullable): string
    {
        return self::primitivePHPValueTypeHint($version, $property->getMemberOf()->getPrimitiveType(), $nullable);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @param bool $asCollection
     * @return string
     */
    public static function primitiveValuePropertyTypeDoc(Version $version, Property $property, bool $nullable, bool $asCollection): string
    {
        return self::primitivePHPReturnValueTypeDoc($version, $property->getMemberOf()->getPrimitiveType(), $nullable, $asCollection);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param bool $nullable
     * @return string
     */
    public static function typeTypeHint(Version $version, Type $type, bool $nullable): string
    {
        $tk = $type->getKind();

        // if this is an inline resource
        if ($tk->isOneOf(TypeKind::RESOURCE_INLINE, TypeKind::RESOURCE_CONTAINER)) {
            return sprintf(
                '%s%s',
                $nullable ? 'null|' : '',
                PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE
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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param bool $nullable
     * @return string
     */
    public static function typeSetterTypeHint(Version $version, Type $type, bool $nullable): string
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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param bool $nullable
     * @param bool $asCollection
     * @return string
     */
    public static function typeTypeDoc(Version $version, Type $type, bool $nullable, bool $asCollection): string
    {
        // if nullable, add to list of additional types
        $types = [];

        // fetch type's kind
        $tk = $type->getKind();

        // if this is an inline resource
        if ($tk->isOneOf(TypeKind::RESOURCE_INLINE, TypeKind::RESOURCE_CONTAINER)) {
            $types[] = $version->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE);
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
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyTypeHint(Version $version, Property $property, bool $nullable): string
    {
        // if this proprety is a collection, the type hint must be a potentially nullable array
        if ($property->isCollection()) {
            return sprintf('%sarray', $nullable ? 'null|' : '');
        }

        // first, check to see if there is a FHIR type for this property value
        $t = $property->getValueFHIRType();

        // if null, the (hopefully) only possibility is that this is a value property for a primitive type
        if (null === $t) {
            return self::primitiveValuePropertyTypeHint($version, $property, $nullable);
        }

        // otherwise, hint as the underlying type
        return self::typeTypeHint($version, $t, $nullable);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyGetterTypeDoc(Version $version, Property $property, bool $nullable): string
    {
        // determine if this property contains a FHIR type or a raw php type
        $t = $property->getValueFHIRType();
        if (null === $t) {
            return self::primitiveValuePropertyTypeDoc($version, $property, $nullable, $property->isCollection());
        }

        return self::typeTypeDoc($version, $t, $nullable, $property->isCollection());
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $asCollection
     * @return string
     */
    public static function propertySetterTypeDoc(Version $version, Property $property, bool $asCollection): string
    {
        // determine if this property contains a FHIR type or a raw php type
        $pt = $property->getValueFHIRType();
        if (null === $pt) {
            return self::primitiveValuePropertyTypeDoc($version, $property, !$asCollection, $asCollection);
        }

        $ptk = $pt->getKind();

        $hintTypes = ['null'];

        if ($ptk === TypeKind::PRIMITIVE_CONTAINER) {
            $ptp = $pt->getLocalProperties()->getProperty('value')->getValueFHIRType();
            $hintTypes = array_merge($hintTypes, $ptp->getPrimitiveType()->getPHPReceiveValueTypeHints());
            array_push(
                $hintTypes,
                self::typeTypeDoc($version, $ptp, false, $asCollection),
                self::typeTypeDoc($version, $pt, false, $asCollection),
            );
        } else if ($ptk->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) {
            $hintTypes = array_merge($hintTypes, $pt->getPrimitiveType()->getPHPReceiveValueTypeHints());
            $hintTypes[] = self::typeTypeDoc($version, $pt, false, $asCollection);
        } else {
            $hintTypes[] = self::typeTypeDoc($version, $pt, false, $asCollection);
        }

        return implode('|', array_unique($hintTypes));
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertySetterTypeHint(Version $version, Property $property, bool $nullable): string
    {
        $pt = $property->getValueFHIRType();
        $ptk = $pt->getKind();

        $hint = self::typeSetterTypeHint($version, $pt, $nullable);

        if ($ptk->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST)) {
            return sprintf('%s|%s', $hint, $pt->getClassName());
        }

        return $hint;
    }
}