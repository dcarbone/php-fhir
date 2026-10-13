<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Config;

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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Per-version profile generation configuration.
 */
class VersionProfilesConfig
{
    private const _CONFIG_KEYS = ['namespace', 'packages'];

    private string $_namespace;
    /** @var array<string, \DCarbone\PHPFHIR\Config\ProfilePackageConfig> */
    private array $_packages = [];

    /**
     * @param null|string $namespace Namespace segment, relative to the version namespace, to place profiles under.
     * @param array<array|\DCarbone\PHPFHIR\Config\ProfilePackageConfig> $packages
     */
    public function __construct(null|string $namespace = null, iterable $packages = [])
    {
        $namespace = trim($namespace ?? PHPFHIR_NAMESPACE_PROFILES, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if (!NameUtils::isValidNSName($namespace)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP namespace.', $namespace));
        }
        $this->_namespace = $namespace;

        foreach ($packages as $package) {
            $this->addPackage($package);
        }
        $this->getOrderedPackages();
    }

    public static function fromArray(array $config): self
    {
        foreach (array_keys($config) as $k) {
            if (!in_array($k, self::_CONFIG_KEYS, true)) {
                throw new \UnexpectedValueException(sprintf(
                    'Unknown profiles config key "%s".  Allowed: %s',
                    $k,
                    implode(', ', self::_CONFIG_KEYS),
                ));
            }
        }
        if (isset($config['packages']) && !is_iterable($config['packages'])) {
            throw new \InvalidArgumentException('Profiles "packages" must be iterable.');
        }
        return new self(
            namespace: $config['namespace'] ?? null,
            packages: $config['packages'] ?? [],
        );
    }

    public function addPackage(array|ProfilePackageConfig $package): self
    {
        if (is_array($package)) {
            $package = ProfilePackageConfig::fromArray($package);
        }
        if (isset($this->_packages[$package->getName()])) {
            throw new \InvalidArgumentException(sprintf(
                'Profile package "%s" has already been added.',
                $package->getName(),
            ));
        }
        foreach ($this->_packages as $existing) {
            if (0 === strcasecmp($existing->getNamespace(), $package->getNamespace())) {
                throw new \DomainException(sprintf(
                    'Profile package "%s" namespace "%s" conflicts with package "%s".',
                    $package->getName(),
                    $package->getNamespace(),
                    $existing->getName(),
                ));
            }
        }
        $this->_packages[$package->getName()] = $package;
        return $this;
    }

    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function hasPackages(): bool
    {
        return [] !== $this->_packages;
    }

    public function hasPackage(string $name): bool
    {
        return isset($this->_packages[$name]);
    }

    public function getPackage(string $name): ProfilePackageConfig
    {
        if (!isset($this->_packages[$name])) {
            throw new \OutOfBoundsException(sprintf('Profile package "%s" is not configured.', $name));
        }
        return $this->_packages[$name];
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\ProfilePackageConfig[]
     */
    public function getPackages(): array
    {
        return array_values($this->_packages);
    }

    /**
     * @return string[]
     */
    public function getPackageNames(): array
    {
        return array_keys($this->_packages);
    }

    /**
     * Packages ordered so that every package comes after the packages it depends on.
     *
     * @return \DCarbone\PHPFHIR\Config\ProfilePackageConfig[]
     * @throws \DomainException If a dependency is not a configured package, or the dependencies are circular.
     */
    public function getOrderedPackages(): array
    {
        $ordered = [];
        $state = [];
        $visit = function (string $name, array $path) use (&$visit, &$ordered, &$state): void {
            if (2 === ($state[$name] ?? 0)) {
                return;
            }
            if (1 === ($state[$name] ?? 0)) {
                throw new \DomainException(sprintf(
                    'Profile package dependencies are circular: %s.',
                    implode(' -> ', [...$path, $name]),
                ));
            }
            $state[$name] = 1;
            foreach ($this->_packages[$name]->getDependencies() as $dep) {
                if (!isset($this->_packages[$dep])) {
                    throw new \DomainException(sprintf(
                        'Profile package "%s" depends on "%s", which is not configured.  Add "%s" to the same version\'s profile packages.',
                        $name,
                        $dep,
                        $dep,
                    ));
                }
                $visit($dep, [...$path, $name]);
            }
            $state[$name] = 2;
            $ordered[] = $this->_packages[$name];
        };
        foreach (array_keys($this->_packages) as $name) {
            $visit($name, []);
        }
        return $ordered;
    }

    /**
     * Names of every package the named package depends on, directly or transitively.
     *
     * @return string[]
     */
    public function getDependencyClosure(string $name): array
    {
        $seen = [];
        $stack = $this->getPackage($name)->getDependencies();
        while ([] !== $stack) {
            $dep = array_pop($stack);
            if (isset($seen[$dep])) {
                continue;
            }
            $seen[$dep] = true;
            array_push($stack, ...$this->getPackage($dep)->getDependencies());
        }
        return array_keys($seen);
    }
}
