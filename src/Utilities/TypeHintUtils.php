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
        $base = $primitiveType->getPHPReturnValueTypeHint();
        if ($stringable && !str_contains($base, 'string')) {
            $base = "string|{$base}";
        }
        return $nullable ? "null|{$base}" : $base;
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
        } else if ($tk->isResourceContainer($version)) {
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
        if ($property->isCollection()) {
            return $nullable ? 'null|array' : 'array';
        }

        $pt = $property->getValueFHIRType();

        if (null === $pt) {
            return self::primitivePHPValueTypeHint(
                $version,
                $property->getMemberOf()->getPrimitiveType(),
                $nullable,
            );
        }

        if ($pt->getKind()->isResourceContainer($version)) {
            return ($nullable ? 'null|' : '') . PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE;
        }

        return ($nullable ? 'null|' : '') . $pt->getClassName();
    }

    /**
     * Builds a TypeDoc hint for a property when returned from a method.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @return string
     */
    public static function propertyGetterDocHint(Version  $version,
                                                 Property $property,
                                                 bool     $nullable): string
    {
        $pt = $property->getValueFHIRType();

        if (null === $pt) {
            return self::primitivePHPValueTypeHint(
                $version,
                $property->getMemberOf()->getPrimitiveType(),
                $nullable,
            );
        }

        if ($pt->getKind()->isResourceContainer($version)) {
            $versionCoreFiles = $version->getCoreFiles();
            $containedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);
            return ($nullable ? 'null|' : '')
                . $containedTypeInterface->getFullyQualifiedName(true)
                . ($property->isCollection() ? '[]' : '');
        }

        $hint = ($nullable ? 'null|' : '') . $pt->getFullyQualifiedClassName(true);

        if ($property->isCollection()) {
            return "{$hint}[]";
        }

        return $hint;
    }

    /**
     * Builds a TypeDoc hint for a property when received as a parameter in a method.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @param bool $ignoreCollection
     * @return string
     */
    public static function buildSetterParameterDocHint(Version  $version,
                                                       Property $property,
                                                       bool     $nullable,
                                                       bool     $ignoreCollection = false): string
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

        $hintTypes = self::buildBaseHintParts($version, $pt, true);

        $ptk = $pt->getKind();

        if ($property->isValueProperty() || $ptk->isOneOf(TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE)) {
            $hintTypes[] = $pt->getFullyQualifiedClassName(true);
        }

        if ($ptk === TypeKindEnum::PRIMITIVE_CONTAINER) {
            $vp = $pt->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            array_push(
                $hintTypes,
                $vp->getValueFHIRType()->getFullyQualifiedClassName(true),
                $pt->getFullyQualifiedClassName(true),
            );
        }

        if (!$ignoreCollection && $property->isCollection()) {
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
     * @param bool $ignoreCollection
     * @return string
     */
    public static function buildSetterParameterHint(Version  $version,
                                                    Property $property,
                                                    bool     $nullable,
                                                    bool     $ignoreCollection = false): string
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

        if (!$ignoreCollection && $property->isCollection()) {
            return $nullable ? 'null|iterable' : 'iterable';
        }

        $hintTypes = self::buildBaseHintParts($version, $pt, false);

        $ptk = $pt->getKind();

        if ($property->isValueProperty() || $ptk->isOneOf(TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE)) {
            $hintTypes[] = $pt->getClassName();
        }

        if ($ptk === TypeKindEnum::PRIMITIVE_CONTAINER) {
            $vp = $pt->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            array_push(
                $hintTypes,
                $vp->getValueFHIRType()->getClassName(),
                $pt->getClassName(),
            );
        }

        if ($nullable) {
            array_unshift($hintTypes, 'null');
        }

        return implode('|', array_unique($hintTypes));
    }
}