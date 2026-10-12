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
 * Everything the profile class template needs to know about one generated profile class.
 */
class ProfileClass
{
    public function __construct(
        private readonly Profile           $_profile,
        private readonly string            $_namespace,
        private readonly string            $_className,
        private readonly string            $_baseTypeClassName,
        private readonly null|ProfileClass $_parent = null,
    ) {
    }

    public function getProfile(): Profile
    {
        return $this->_profile;
    }

    /** Namespace without leading slash. */
    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function getClassName(): string
    {
        return $this->_className;
    }

    public function getFullyQualifiedClassName(bool $leadingSlash): string
    {
        return ($leadingSlash ? '\\' : '') . $this->_namespace . '\\' . $this->_className;
    }

    /** Fully qualified name of the FHIR type this profile constrains, without leading slash. */
    public function getBaseTypeClassName(): string
    {
        return $this->_baseTypeClassName;
    }

    public function getParent(): null|ProfileClass
    {
        return $this->_parent;
    }

    /** Fully qualified name, with leading slash, of the class this one extends. */
    public function getExtendsClassName(): string
    {
        return null !== $this->_parent
            ? $this->_parent->getFullyQualifiedClassName(true)
            : '\\' . $this->_baseTypeClassName;
    }

    public static function sanitizeClassName(string $name): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $out = implode('', array_map(static fn(string $p): string => ucfirst($p), $parts));
        if ('' === $out) {
            throw new ProfileDefinitionException(sprintf('Cannot derive a class name from "%s"', $name));
        }
        if (ctype_digit($out[0])) {
            $out = 'Profile' . $out;
        }
        return $out;
    }
}
