<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

class TypeHintUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType
     * @param bool $nullable
     * @param bool $stringable
     * @return string
     */
    public static function primitivePHPValueTypeHint(Version           $version,
                                                     PrimitiveTypeEnum $primitiveType,
                                                     bool              $nullable,
                                                     bool              $stringable = false): string
    {
        return ($nullable ? 'null|' : '')
            . ($stringable ? 'string|' : '')
            . $primitiveType->getPHPReturnValueTypeHint();
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType
     * @param bool $nullable
     * @param bool $asArray
     * @param bool $asVariadic
     * @return string
     */
    public static function primitivePHPValueTypeSetterDoc(Version           $version,
                                                          PrimitiveTypeEnum $primitiveType,
                                                          bool              $nullable,
                                                          bool              $asArray = false,
                                                          bool              $asVariadic = false): string
    {
        if ($asArray && $asVariadic) {
            throw new \InvalidArgumentException('Cannot set both array and variadic');
        }
        if ($asVariadic && $nullable) {
            throw new \InvalidArgumentException('Cannot set both nullable and variadic');
        }

        $hintTypes = $primitiveType->getPHPReceiveValueTypeHints();

        if ($asArray) {
            $hintTypes[] = array_map(function (string $v) {
                return sprintf('%s[]', $v);
            }, $hintTypes);
        }

        if ($nullable) {
            array_unshift($hintTypes, 'null');
        }

        if ($asVariadic) {
            return sprintf('...%s', implode('|', $hintTypes));
        }

        return implode('|', $hintTypes);
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param bool $nullable
     * @return string
     */
    public static function typeHint(Version $version, Type $type, bool $nullable): string
    {
        $tk = $type->getKind();

        // if this is an inline resource
        if ($tk->isOneOf(TypeKindEnum::RESOURCE_INLINE, TypeKindEnum::RESOURCE_CONTAINER)) {
            return sprintf(
                '%s%s',
                $nullable ? 'null|' : '',
                PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE
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

        if ($tk->isResourceContainer($version)) {
            $types[] = PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE;
        } else if ($tk === TypeKindEnum::PRIMITIVE_CONTAINER) {
            $pt = $type->getProperties()->getProperty('value')->getValueFHIRType();
            $types = array_merge($types, $pt->getPrimitiveType()->getPHPReceiveValueTypeHints());
            array_push(
                $types,
                $pt->getClassName(),
                $type->getClassName(),
            );
        } else if ($tk->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) {
            $types = array_merge($types, $type->getprimitiveType()->getPHPReceiveValueTypeHints());
        } else {
            $types[] = $type->getClassName();
        }

        return implode('|', array_unique($types));
    }

    /**
     * Compiles base array of hint components for a given FHIR type.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @param bool $fullyQualified
     * @return array
     */
    public static function buildBaseHintParts(Version $version, Type $type, bool $fullyQualified): array
    {
        // fetch type's kind
        $tk = $type->getKind();

        if ($tk->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) {
            $hintTypes = $type->getPrimitiveType()->getPHPReceiveValueTypeHints();
        } else if ($tk === TypeKindEnum::PRIMITIVE_CONTAINER) {
            $ptp = $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME)->getValueFHIRType();
            $hintTypes = $ptp->getPrimitiveType()->getPHPReceiveValueTypeHints();
            array_merge($hintTypes, self::buildBaseHintParts($version, $ptp, $fullyQualified));
        } else if ($tk->isOneOf(TypeKindEnum::RESOURCE_INLINE, TypeKindEnum::RESOURCE_CONTAINER)) {
            $hintTypes = [
                match ($fullyQualified) {
                    true => $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE),
                    false => PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE,
                },
            ];
        } else {
            $hintTypes = [
                match ($fullyQualified) {
                    true => $type->getFullyQualifiedClassName(true),
                    false => $type->getClassName(),
                },
            ];
        }

        return array_unique($hintTypes);
    }

    /**
     * Builds the type hint for a property declaration.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyDeclarationHint(Version $version, Property $property, bool $nullable): string
    {
        // if this proprety is a collection, the type hint must be a potentially nullable array
        if ($property->isCollection()) {
            return sprintf('%sarray', $nullable ? 'null|' : '');
        }

        // first, check to see if there is a FHIR type for this property value
        $t = $property->getValueFHIRType();

        // if null, the (hopefully) only possibility is that this is a value property for a primitive type
        if (null === $t) {
            return self::primitivePHPValueTypeHint(
                $version,
                $property->getMemberOf()->getPrimitiveType(),
                $nullable,
            );
        }

        // otherwise, hint as the underlying type
        return self::typeHint($version, $t, $nullable);
    }

    /**
     * Builds a TypeDoc hint for a property when returned from a method.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyGetterTypeDocHint(Version  $version,
                                                     Property $property,
                                                     bool     $nullable): string
    {
        $t = $property->getValueFHIRType();
        if (null === $t) {
            return self::primitivePHPValueTypeHint(
                $version,
                $property->getMemberOf()->getPrimitiveType(),
                $nullable,
            );
        }

        $hintTypes = self::buildBaseHintParts($version, $t, false);

        if ($property->isCollection()) {
            $hintTypes = array_map(fn(string $n) => "{$n}[]", $hintTypes);
        }

        if ($nullable) {
            array_unshift($hintTypes, 'null');
        }

        return implode('|', $hintTypes);
    }

    /**
     * Builds a TypeDoc hint for a property when received as a parameter in a method.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function buildConstructorParameterDocHint(Version  $version,
                                                            Property $property,
                                                            bool     $nullable): string
    {
        $pt = $property->getValueFHIRType();

        if ($property->isValueProperty()) {
            if ($property->getMemberOf()->getKind() !== TypeKindEnum::PRIMITIVE_CONTAINER) {
                return self::primitivePHPValueTypeHint(
                    version: $version,
                    primitiveType: $property->getMemberOf()->getPrimitiveType(),
                    nullable: $nullable,
                    stringable: true,
                );
            }
            $hintTypes = [
                'null',
            ];
        } else {
            $hintTypes = self::buildBaseHintParts($version, $pt, true);
        }



        $ptk = $pt->getKind();

        if ($ptk->isOneOf(TypeKindEnum::PRIMITIVE_CONTAINER)) {
            $vp = $pt->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            array_push(
                $hintTypes,
                $vp->getValueFHIRType()->getFullyQualifiedClassName(true),
                $pt->getFullyQualifiedClassName(true),
            );
        }

        if ($property->isCollection()) {
            $hintTypes = array_map(fn(string $n) => "{$n}[]", $hintTypes);
        }

        if ($nullable) {
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
    public static function buildConstructorParameterHint(Version  $version,
                                                         Property $property,
                                                         bool     $nullable): string
    {
        $pt = $property->getValueFHIRType();
        if (null === $pt) {
            return self::primitivePHPValueTypeHint(
                version: $version,
                primitiveType: $property->getMemberOf()->getPrimitiveType(),
                nullable: $nullable,
                stringable: true,
            );
        }

        if ($property->isCollection()) {
            return $nullable ? 'null|array' : 'array';
        }

        $hintTypes = self::buildBaseHintParts($version, $pt, false);

        $ptk = $pt->getKind();

        if ($ptk->isOneOf(TypeKindEnum::PRIMITIVE_CONTAINER)) {
            $vp = $pt->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            array_push(
                $hintTypes,
                $vp->getValueFHIRType()->getClassName(),
                $pt->getClassName(),
            );
        }

        if ($property->isCollection()) {
            $hintTypes = array_map(fn(string $n) => "{$n}[]", $hintTypes);
        }

        if ($nullable) {
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
    public static function propertySetterTypeHint(Version $version, Property $property, bool $nullable): string
    {
        $pt = $property->getValueFHIRType();
        $ptk = $pt->getKind();

        $hint = self::typeSetterTypeHint($version, $pt, $nullable);

        if ($ptk->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) {
            return sprintf('%s|%s', $hint, $pt->getClassName());
        }

        return $hint;
    }
}