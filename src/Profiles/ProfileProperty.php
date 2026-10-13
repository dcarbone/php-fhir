<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * One property of a generated FHIR type, as far as profile validation code generation is concerned.
 */
final class ProfileProperty
{
    /** A type with properties of its own. */
    public const KIND_COMPLEX = 'complex';
    /** A primitive wrapper, e.g. FHIRString; its raw value is $x->getValue()?->getValue(). */
    public const KIND_CONTAINER = 'container';
    /** A primitive value type, e.g. FHIRStringPrimitive; its raw value is $x->getValue(). */
    public const KIND_PRIMITIVE = 'primitive';

    /**
     * @param string $field FHIR name of the field, e.g. "birthDate".
     * @param string $getter Name of the getter method, e.g. "getBirthDate".
     * @param bool $collection Whether the getter returns an array of values.
     * @param string $typeKey Opaque key of the property's type, to be handed back to the navigator.
     * @param string $className Fully qualified class name, without leading slash, of the property's type.
     * @param string $kind One of the KIND_* constants.
     */
    public function __construct(
        public readonly string $field,
        public readonly string $getter,
        public readonly bool   $collection,
        public readonly string $typeKey,
        public readonly string $className,
        public readonly string $kind = self::KIND_COMPLEX,
    ) {
    }

    public function isComplex(): bool
    {
        return self::KIND_COMPLEX === $this->kind;
    }
}
