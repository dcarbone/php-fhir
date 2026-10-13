<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

use DCarbone\PHPFHIR\Builder\Imports;

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
        private readonly string            $_profilesNamespace = '',
        private readonly array             $_compiled = ['rules' => [], 'slices' => [], 'invariants' => [], 'skipped' => []],
        private readonly null|ProfileTypeNavigator $_navigator = null,
        private readonly array             $_package = [],
    ) {
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

    private null|Imports $_imports = null;
    private null|ProfileValidationCode $_code = null;

    /** Generates the validation code, once, along with the imports it and the class need. */
    private function _build(): void
    {
        if (null !== $this->_imports) {
            return;
        }
        $imports = new Imports(null, $this->_namespace, $this->_className);
        [$name, $ns] = $this->_split($this->_extendsFQN());
        $imports->addImport($ns, $name);
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
     * @return array<int, array{method: string, getter: string, matcher: string, class: string, single: bool, slice: int}>
     */
    public function getSliceAccessors(): array
    {
        $matchers = $this->getValidationCode()->sliceMatchers;
        $out = [];
        $used = [];
        foreach ($this->_compiled['slices'] as $i => $slice) {
            if (!isset($matchers[$i]) || str_contains($slice['path'], '.') || str_contains($slice['path'], '[x]')) {
                continue;
            }
            $method = 'get' . self::sanitizeClassName($slice['name']) . ucfirst($slice['path']);
            if (isset($used[strtolower($method)])) {
                continue;
            }
            $used[strtolower($method)] = true;
            $out[] = [
                'method' => $method,
                'getter' => 'get' . ucfirst($slice['path']),
                'matcher' => $matchers[$i]['method'],
                'class' => $matchers[$i]['class'],
                'single' => 1 === ($slice['max'] ?? null),
                'slice' => $i,
            ];
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
        [$name, $ns] = $this->_split($fqn);
        return $imports->getImportByClassAndNamespace($name, $ns)?->getImportedName() ?? $name;
    }

    private function _extendsFQN(): string
    {
        return null !== $this->_parent
            ? $this->_parent->getFullyQualifiedClassName(false)
            : $this->_baseTypeClassName;
    }

    /** @return array{0: string, 1: string} class name, namespace */
    private function _split(string $fqn): array
    {
        $pos = strrpos($fqn, '\\');
        return [substr($fqn, $pos + 1), substr($fqn, 0, $pos)];
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
