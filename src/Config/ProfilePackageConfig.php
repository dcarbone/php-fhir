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
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Configuration for a single FHIR profile package (e.g. hl7.fhir.us.core) to generate profile classes from.
 */
class ProfilePackageConfig
{
    private const _NAME_REGEX = '{^[a-z0-9][a-z0-9._\-]*$}';
    private const _IGNORED_NAME_SEGMENTS = ['hl7', 'fhir'];
    private const _CONFIG_KEYS = ['name', 'source', 'namespace', 'dependencies', 'strict'];

    private string $_name;
    private string $_source;
    private string $_namespace;
    /** @var string[] */
    private array $_dependencies;
    private bool $_strict;

    /**
     * @param string $name NPM-style package name, e.g. "hl7.fhir.us.core".
     * @param string $source Path to either a package .tgz file or an extracted package directory.
     * @param null|string $namespace Namespace segment to place classes under.  Derived from $name if null.
     * @param string[] $dependencies Names of other packages configured for the same version that this one builds on.
     *                               They are generated first and their profiles, extensions and ValueSets are visible here.
     * @param bool $strict If true, unresolvable profile elements cause generation failure instead of a warning.
     */
    public function __construct(string      $name,
                                string      $source,
                                null|string $namespace = null,
                                array       $dependencies = [],
                                bool        $strict = true)
    {
        $name = trim($name);
        if ('' === $name || !preg_match(self::_NAME_REGEX, $name)) {
            throw new InvalidArgumentException(sprintf(
                'Profile package name "%s" is invalid: must be a lowercase, npm-style name such as "hl7.fhir.us.core".',
                $name,
            ));
        }
        $this->_name = $name;

        $this->_source = self::_assertReadablePath($source, sprintf('Profile package "%s" source', $name));

        if (null === $namespace) {
            $namespace = self::deriveNamespace($name);
        }
        $namespace = trim($namespace, PHPFHIR_NAMESPACE_TRIM_CUTSET);
        if (!NameUtils::isValidNSName($namespace)) {
            throw new InvalidArgumentException(sprintf(
                'Profile package "%s" namespace "%s" is not a valid PHP namespace.  Specify "namespace" explicitly.',
                $name,
                $namespace,
            ));
        }
        $this->_namespace = $namespace;

        $this->_dependencies = [];
        if (!array_is_list($dependencies)) {
            throw new InvalidArgumentException(sprintf(
                'Profile package "%s" dependencies must be a list of the names of other configured profile packages.',
                $name,
            ));
        }
        foreach ($dependencies as $depName) {
            if (!is_string($depName) || 1 !== preg_match(self::_NAME_REGEX, trim($depName))) {
                throw new InvalidArgumentException(sprintf(
                    'Profile package "%s" dependencies must be a list of the names of other configured profile packages.',
                    $name,
                ));
            }
            $depName = trim($depName);
            if ($depName === $name) {
                throw new InvalidArgumentException(sprintf('Profile package "%s" cannot depend on itself.', $name));
            }
            if (in_array($depName, $this->_dependencies, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Profile package "%s" lists dependency "%s" more than once.',
                    $name,
                    $depName,
                ));
            }
            $this->_dependencies[] = $depName;
        }

        $this->_strict = $strict;
    }

    public static function fromArray(array $config): self
    {
        foreach (array_keys($config) as $k) {
            if (!in_array($k, self::_CONFIG_KEYS, true)) {
                throw new UnexpectedValueException(sprintf(
                    'Unknown profile package config key "%s".  Allowed: %s',
                    $k,
                    implode(', ', self::_CONFIG_KEYS),
                ));
            }
        }
        if (!isset($config['name'])) {
            throw new InvalidArgumentException('Profile package "name" is required.');
        }
        if (!isset($config['source'])) {
            throw new InvalidArgumentException('Profile package "source" is required.');
        }
        if (isset($config['strict']) && !is_bool($config['strict'])) {
            throw new InvalidArgumentException('Profile package "strict" must be a bool.');
        }
        return new self(
            name: $config['name'],
            source: $config['source'],
            namespace: $config['namespace'] ?? null,
            dependencies: $config['dependencies'] ?? [],
            strict: $config['strict'] ?? true,
        );
    }

    /**
     * Converts a package name into a PascalCase namespace segment, upper casing segments of three characters or fewer, e.g. "hl7.fhir.us.core" => "USCore".
     *
     * @param string $packageName
     * @return string
     */
    public static function deriveNamespace(string $packageName): string
    {
        $segments = preg_split('{[._\-]+}', $packageName, -1, PREG_SPLIT_NO_EMPTY);
        while ([] !== $segments && in_array($segments[0], self::_IGNORED_NAME_SEGMENTS, true)) {
            array_shift($segments);
        }
        $out = '';
        foreach ($segments as $segment) {
            // short segments are acronyms: "us", "uv", "sdc", "r4"
            $out .= strlen($segment) <= 3 ? strtoupper($segment) : ucfirst($segment);
        }
        return $out;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getSource(): string
    {
        return $this->_source;
    }

    public function isSourceArchive(): bool
    {
        return is_file($this->_source);
    }

    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    /**
     * @return string[] Names of the packages this one depends on.
     */
    public function getDependencies(): array
    {
        return $this->_dependencies;
    }

    public function isStrict(): bool
    {
        return $this->_strict;
    }

    private static function _assertReadablePath(string $path, string $what): string
    {
        if ('' === trim($path) || !file_exists($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf(
                '%s "%s" either does not exist or is not readable.',
                $what,
                $path,
            ));
        }
        if (!is_dir($path) && !is_file($path)) {
            throw new InvalidArgumentException(sprintf('%s "%s" must be a file or directory.', $what, $path));
        }
        return $path;
    }
}
