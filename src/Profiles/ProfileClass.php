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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Everything the profile class template needs to know about one generated profile class.
 */
class ProfileClass
{
    private null|Imports $_imports = null;
    private null|ProfileValidationCode $_code = null;
    private readonly Profile $_profile;
    private readonly string $_namespace;
    private readonly string $_className;
    private readonly string $_baseTypeClassName;
    private readonly null|ProfileClass $_parent;
    private readonly string $_profilesNamespace;
    private readonly array $_compiled;
    private readonly null|ProfileTypeNavigator $_navigator;
    private readonly array $_package;
    private readonly array $_support;
    private readonly string $_packageNamespace;

    public function __construct(
        Profile $_profile,
        string $_namespace,
        string $_className,
        string $_baseTypeClassName,
        null|ProfileClass $_parent = null,
        string $_profilesNamespace = '',
        array $_compiled = ['rules' => [], 'slices' => [], 'invariants' => [], 'skipped' => []],
        null|ProfileTypeNavigator $_navigator = null,
        array $_package = [],
        array $_support = [],
        string $_packageNamespace = '',
    ) {
        $this->_profile = $_profile;
        $this->_namespace = $_namespace;
        $this->_className = $_className;
        $this->_baseTypeClassName = $_baseTypeClassName;
        $this->_parent = $_parent;
        $this->_profilesNamespace = $_profilesNamespace;
        $this->_compiled = $_compiled;
        $this->_navigator = $_navigator;
        $this->_package = $_package;
        $this->_support = $_support;
        $this->_packageNamespace = '' === $_packageNamespace ? $_namespace : $_packageNamespace;
    }

    /**
     * Text for the "Profile Copyright Notice" of the class header: where the profile comes from, how the package is
     * licensed, and the copyright statement of the profile itself.
     */
    public function getCopyrightNotice(): null|string
    {
        $lines = [];
        if (isset($this->_package['name'])) {
            $lines[] = sprintf('Package: %s %s', $this->_package['name'], $this->_package['version'] ?? '');
        }
        if (isset($this->_package['author'])) {
            $lines[] = 'Author: ' . $this->_package['author'];
        }
        if (isset($this->_package['license'])) {
            $lines[] = 'License: ' . $this->_package['license'];
        }
        $text = rtrim(implode("\n", $lines));
        if (null !== $this->_profile->getCopyright()) {
            $text .= ('' === $text ? '' : "\n\n") . $this->_profile->getCopyright();
        }
        return '' === $text ? null : $text;
    }

    /** Generates the validation code, once, along with the imports it and the class need. */
    private function _build(): void
    {
        if (null !== $this->_imports) {
            return;
        }
        $imports = new Imports(null, $this->_namespace, $this->_className);
        $imports->addImport(...NameUtils::splitFullyQualifiedName($this->_extendsFQN()));
        foreach ($this->_support as $key => $fqn) {
            // the profile interface is implemented by the root of a profile hierarchy only.
            if ('profileInterface' === $key && null !== $this->_parent) {
                continue;
            }
            $imports->addImport(...NameUtils::splitFullyQualifiedName($fqn));
        }
        $this->_code = null === $this->_navigator
            ? new ProfileValidationCode()
            : ProfileValidationGenerator::generate(
                $this->_compiled,
                $this->_profile->getType(),
                $this->_navigator,
                $imports,
            );
        $this->_imports = $imports;
    }

    /** Name, as written in the class body, of the core class that matches values against patterns. */
    public function getValueMatcherName(): string
    {
        return $this->_supportName('matcher', PHPFHIR_VALIDATION_CLASSNAME_VALUE_MATCHER);
    }

    /** Name, as written in the class body, of the core class that holds the validation rule registry. */
    public function getValidatorName(): string
    {
        return $this->_supportName('validator', PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);
    }

    /**
     * Name, as written in the class body, of the core interface implemented by profile classes.  Null if this class
     * inherits it from its parent profile class, or if it is not available.
     */
    public function getProfileInterfaceName(): null|string
    {
        return null === $this->_parent && isset($this->_support['profileInterface'])
            ? $this->_importedName($this->getImports(), $this->_support['profileInterface'])
            : null;
    }

    private function _supportName(string $key, string $default): string
    {
        return isset($this->_support[$key]) ? $this->_importedName($this->getImports(), $this->_support[$key]) : $default;
    }

    public function getValidationCode(): ProfileValidationCode
    {
        $this->_build();
        return $this->_code;
    }

    /** Namespace, without leading slash, shared by the generated validator, map and autoloader. */
    public function getProfilesNamespace(): string
    {
        return $this->_profilesNamespace;
    }

    /** @return array<int, array<string, mixed>> */
    public function getRules(): array
    {
        return $this->_compiled['rules'];
    }

    /** @return array<int, array<string, mixed>> */
    public function getSlices(): array
    {
        return $this->_compiled['slices'];
    }

    /** @return array<int, array<string, null|string>> */
    public function getInvariants(): array
    {
        return $this->_compiled['invariants'];
    }

    /** @return string[] Constraints that could not be turned into rules, and why. */
    public function getSkipped(): array
    {
        $this->_build();
        return [...$this->_compiled['skipped'], ...$this->_code->skipped];
    }

    /**
     * Typed accessors for slices of the profile's own top-level elements, e.g. an "ssn" slice of "identifier"
     * becomes getSsnIdentifier().
     *
     * @return array<int, array{method: string, getter: string, constant: string, class: string, single: bool, slice: int}>
     */
    public function getSliceAccessors(): array
    {
        $matchers = $this->getValidationCode()->sliceMatchers;
        $inherited = $this->_inheritedSliceAccessorShapes();
        $out = [];
        $used = [];
        foreach ($this->_compiled['slices'] as $i => $slice) {
            $sliceName = NameUtils::toPascalCase($slice['name']);
            if ('' === $sliceName || !isset($matchers[$i]) || str_contains($slice['path'], '.') || str_contains($slice['path'], '[x]')) {
                continue;
            }
            $method = NameUtils::getGetterName($sliceName . ucfirst($slice['path']));
            if (isset($used[strtolower($method)])) {
                continue;
            }
            $used[strtolower($method)] = true;
            $out[] = [
                'method' => $method,
                'getter' => NameUtils::getGetterName($slice['path']),
                'constant' => $matchers[$i]['constant'],
                'class' => $matchers[$i]['class'],
                // an override must keep the signature of the accessor it overrides, even if this profile narrows max
                'single' => $inherited[strtolower($method)] ?? 1 === ($slice['max'] ?? null),
                'slice' => $i,
            ];
        }
        return $out;
    }

    /**
     * @return array<string, bool> lowercased method name => whether the nearest ancestor declaring it returns a single
     *                             value.
     */
    private function _inheritedSliceAccessorShapes(): array
    {
        $out = [];
        for ($parent = $this->_parent; null !== $parent; $parent = $parent->getParent()) {
            foreach ($parent->getSliceAccessors() as $accessor) {
                $out[strtolower($accessor['method'])] ??= $accessor['single'];
            }
        }
        return $out;
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

    /** Namespace, without leading slash, of the package the class belongs to.  The class itself is nested beneath it. */
    public function getPackageNamespace(): string
    {
        return $this->_packageNamespace;
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

    /**
     * Imports needed by the class, including everything its generated validation code refers to.
     */
    public function getImports(): Imports
    {
        $this->_build();
        return $this->_imports;
    }

    /** Name, as written in the class body, of the class this one extends. */
    public function getExtendsName(): string
    {
        return $this->_importedName($this->getImports(), $this->_extendsFQN());
    }

    private function _importedName(Imports $imports, string $fqn): string
    {
        [$ns, $name] = NameUtils::splitFullyQualifiedName($fqn);
        return $imports->getImportByClassAndNamespace($name, $ns)?->getImportedName() ?? $name;
    }

    private function _extendsFQN(): string
    {
        return null !== $this->_parent
            ? $this->_parent->getFullyQualifiedClassName(false)
            : $this->_baseTypeClassName;
    }
}
