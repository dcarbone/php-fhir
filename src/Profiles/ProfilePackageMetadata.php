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
 * The descriptive fields of a FHIR NPM-style package manifest (package.json).
 */
class ProfilePackageMetadata
{
    private readonly string $_name;
    private readonly string $_version;
    private readonly null|string $_canonical;
    private readonly null|string $_author;
    private readonly null|string $_license;
    private readonly array $_fhirVersions;
    private readonly array $_declaredDependencies;

    /**
     * @param string[] $fhirVersions FHIR versions the package declares support for.
     * @param array<string, string> $declaredDependencies Declared dependencies as package name => version.
     */
    public function __construct(
        string $name,
        string $version,
        null|string $canonical = null,
        null|string $author = null,
        null|string $license = null,
        array $fhirVersions = [],
        array $declaredDependencies = [],
    ) {
        $this->_name = $name;
        $this->_version = $version;
        $this->_canonical = $canonical;
        $this->_author = $author;
        $this->_license = $license;
        $this->_fhirVersions = $fhirVersions;
        $this->_declaredDependencies = $declaredDependencies;
    }

    /**
     * @param array<string, mixed> $manifest Decoded package.json.
     */
    public static function fromManifest(array $manifest): self
    {
        return new self(
            $manifest['name'],
            $manifest['version'],
            self::_optionalString($manifest, 'canonical'),
            self::_optionalString($manifest, 'author'),
            self::_optionalString($manifest, 'license'),
            array_values((array)($manifest['fhirVersions'] ?? [])),
            (array)($manifest['dependencies'] ?? []),
        );
    }

    private static function _optionalString(array $manifest, string $key): null|string
    {
        if (!isset($manifest[$key]) || !is_string($manifest[$key]) || '' === trim($manifest[$key])) {
            return null;
        }
        return trim($manifest[$key]);
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getVersion(): string
    {
        return $this->_version;
    }

    public function getCanonical(): null|string
    {
        return $this->_canonical;
    }

    public function getAuthor(): null|string
    {
        return $this->_author;
    }

    public function getLicense(): null|string
    {
        return $this->_license;
    }

    /** @return string[] */
    public function getFHIRVersions(): array
    {
        return $this->_fhirVersions;
    }

    /** @return array<string, string> package name => version */
    public function getDeclaredDependencies(): array
    {
        return $this->_declaredDependencies;
    }
}
