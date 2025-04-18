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
     * @return string
     */
    public static function primitivePHPValueTypeHint(Version           $version,
                                                     PrimitiveTypeEnum $primitiveType,
                                                     bool              $nullable): string
    {
        $base = $primitiveType->getPHPReturnValueTypeHint();
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
                return sprintf('iterable<%s>', $v);
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
    public static function typeSetterTypeHint(Version $version, Type $type, bool $nullable): string
    {
        $tk = $type->getKind();
        $types = $nullable ? ['null'] : [];

        if ($tk->isResourceContainer($version)) {
            $types[] = PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE;
        } else if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) {
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
     * @throws \Exception
     */
    public static function buildBaseHintParts(Version $version, Type $type, bool $fullyQualified): array
    {
        // fetch type's kind
        $tk = $type->getKind();

        if ($type->isPrimitiveType() || $type->hasPrimitiveTypeParent()) {
            $hintTypes = $type->getPrimitiveType()->getPHPReceiveValueTypeHints();
        } else if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) {
            $valProp = match (true) {
                $type->isPrimitiveContainer() => $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME),
                $type->hasPrimitiveContainerParent() => $type->getParentProperty(PHPFHIR_VALUE_PROPERTY_NAME),
                default => null,
            };
            $ptp = $valProp->getValueFHIRType();
            $hintTypes = [];
            if ($ptp->isPrimitiveType() || $ptp->hasPrimitiveTypeParent()) {
                $hintTypes = $ptp->getPrimitiveType()->getPHPReceiveValueTypeHints();
            }
            array_merge($hintTypes, self::buildBaseHintParts($version, $ptp, $fullyQualified));
        } else if ($tk->isResourceContainer($version)) {
            $containerType = $version->getDefinition()->getTypes()->getContainerType();
            $hintTypes = match ($fullyQualified) {
                true => [
                    $containerType->getFullyQualifiedClassName(true),
                    $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE),
                ],
                false => [
                    $containerType->getClassName(),
                    PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE,
                ],
            };
        } else if ($type->getKind() === TypeKindEnum::PHPFHIR_XHTML) {
            $hintTypes = [
                'string',
                '\\SimpleXMLElement',
                '\\DOMNode',
                match ($fullyQualified) {
                    true => $type->getFullyQualifiedClassName(true),
                    false => $type->getClassName(),
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
            return 'array';
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
                $nullable && !$property->isCollection(),
            );
        }

        if ($pt->getKind()->isResourceContainer($version)) {
            $versionCoreFiles = $version->getVersionCoreFiles();
            $containedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);
            if ($property->isCollection()) {
                return "iterable<{$containedTypeInterface->getFullyQualifiedName(true)}>";
            }
            return ($nullable ? 'null|' : '') . $containedTypeInterface->getFullyQualifiedName(true);
        }

        if ($property->isCollection()) {
            return "iterable<{$pt->getFullyQualifiedClassName(true)}>";
        }

        return ($nullable ? 'null|' : '') . $pt->getFullyQualifiedClassName(true);
    }

    /**
     * Builds a TypeDoc hint for a property when received as a parameter in a method.
     *
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @param bool $nullable
     * @param bool $ignoreCollection
     * @return string
     * @throws \Exception
     */
    public static function buildSetterParameterDocHint(Version  $version,
                                                       Property $property,
                                                       bool     $nullable,
                                                       bool     $ignoreCollection = false): string
    {
        $pt = $property->getValueFHIRType();

        if (null === $pt) {
            return self::primitivePHPValueTypeSetterDoc(
                version: $version,
                primitiveType: $property->getMemberOf()->getPrimitiveType(),
                nullable: $nullable,
            );
        }

        $hintTypes = self::buildBaseHintParts($version, $pt, true);

        if ($pt->isPrimitiveContainer() || $pt->hasPrimitiveContainerParent()) {
            $vp = $pt->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME) ?? $pt->getParentProperty(PHPFHIR_VALUE_PROPERTY_NAME);
            array_push(
                $hintTypes,
                $vp->getValueFHIRType()->getFullyQualifiedClassName(true),
                $pt->getFullyQualifiedClassName(true),
            );
        } else if ($property->isValueProperty() || ($pt->isPrimitiveType() || $pt->hasPrimitiveTypeParent())) {
            $hintTypes[] = $pt->getFullyQualifiedClassName(true);
        }

        if (!$ignoreCollection && $property->isCollection()) {
            $hintTypes = array_map(fn(string $n) => "iterable<{$n}>", $hintTypes);
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
     * @throws \Exception
     */
    public static function buildSetterParameterHint(Version  $version,
                                                    Property $property,
                                                    bool     $nullable,
                                                    bool     $ignoreCollection = false): string
    {
        if (!$ignoreCollection && $property->isCollection()) {
            return $nullable ? 'null|iterable' : 'iterable';
        }

        $pt = $property->getValueFHIRType();

        if (null === $pt) {
            $hintTypes = $property->getMemberOf()->getPrimitiveType()->getPHPReceiveValueTypeHints();
        } else {
            $hintTypes = self::buildBaseHintParts($version, $pt, false);

            if ($pt->isPrimitiveContainer() || $pt->hasPrimitiveContainerParent()) {
                $vp = $pt->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME) ?? $pt->getParentProperty(PHPFHIR_VALUE_PROPERTY_NAME);
                array_push(
                    $hintTypes,
                    $vp->getValueFHIRType()->getClassName(),
                    $pt->getClassName(),
                );
            } else if ($property->isValueProperty() || ($pt->isPrimitiveType() || $pt->hasPrimitiveTypeParent())) {
                $hintTypes[] = $pt->getClassName();
            }
        }

        if ($nullable) {
            array_unshift($hintTypes, 'null');
        }

        return implode('|', array_unique($hintTypes));
    }
}