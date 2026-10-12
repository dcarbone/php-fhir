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

use DCarbone\PHPFHIR\Config\ProfilePackageConfig;

/**
 * Reads FHIR NPM-style packages (https://hl7.org/fhir/packages.html) from either an extracted directory or a
 * .tgz archive.  No network access is performed.
 */
class ProfilePackageReader
{
    private const _MANIFEST_FILENAME = 'package.json';
    private const _PACKAGE_DIR = 'package';

    /**
     * @param \DCarbone\PHPFHIR\Config\ProfilePackageConfig $config
     * @param null|string $fhirVersion The FHIR version (e.g. "4.0.1") the package must support.  If null, no version
     *                                 compatibility check is performed.
     * @return \DCarbone\PHPFHIR\Profiles\ProfilePackage
     * @throws \DCarbone\PHPFHIR\Profiles\ProfilePackageException
     */
    public static function read(ProfilePackageConfig $config, null|string $fhirVersion = null): ProfilePackage
    {
        $package = self::_readSource($config, $config->getSource(), $config->getName());

        if (null !== $fhirVersion) {
            self::_assertFHIRVersionCompatible($package, $fhirVersion);
        }

        foreach ($config->getDependencies() as $depName => $depPath) {
            self::_loadDependency($package, $config, $depName, $depPath, $fhirVersion);
        }

        return $package;
    }

    /**
     * Compares two FHIR version strings on Major.Minor only, ignoring any pre-release suffix.
     */
    public static function isFHIRVersionCompatible(string $packageFHIRVersion, string $fhirVersion): bool
    {
        $a = self::_majorMinor($packageFHIRVersion);
        $b = self::_majorMinor($fhirVersion);
        return null !== $a && $a === $b;
    }

    private static function _majorMinor(string $version): null|string
    {
        if (1 !== preg_match('{^v?(\d+)\.(\d+)(?:\.\d+)?(?:-[0-9A-Za-z.\-]+)?$}', trim($version), $m)) {
            return null;
        }
        return $m[1] . '.' . $m[2];
    }

    private static function _assertFHIRVersionCompatible(ProfilePackage $package, string $fhirVersion): void
    {
        $declared = $package->getFHIRVersions();
        // packages that do not declare a FHIR version cannot be verified.
        if ([] === $declared) {
            return;
        }
        foreach ($declared as $candidate) {
            if (is_string($candidate) && self::isFHIRVersionCompatible($candidate, $fhirVersion)) {
                return;
            }
        }
        throw new ProfilePackageException(sprintf(
            'Profile package "%s" supports FHIR version(s) [%s], which is not compatible with %s.',
            $package->getName(),
            implode(', ', array_map('strval', $declared)),
            $fhirVersion,
        ));
    }

    private static function _loadDependency(ProfilePackage       $parent,
                                            ProfilePackageConfig $rootConfig,
                                            string               $name,
                                            string               $path,
                                            null|string          $fhirVersion): void
    {
        $depConfig = new ProfilePackageConfig(
            name: $name,
            source: $path,
            namespace: 'Dependency',
            strict: $rootConfig->isStrict(),
        );
        $dependency = self::_readSource($depConfig, $path, $name);
        if (null !== $fhirVersion) {
            self::_assertFHIRVersionCompatible($dependency, $fhirVersion);
        }
        $parent->addLoadedDependency($dependency);
    }

    private static function _readSource(ProfilePackageConfig $config, string $source, string $expectedName): ProfilePackage
    {
        $manifest = null;
        $resources = [];

        foreach (self::_iterateFiles($source) as $relativeName => $contents) {
            $json = self::_decode($contents, $relativeName, $source);
            if (self::_MANIFEST_FILENAME === $relativeName) {
                $manifest = $json;
                continue;
            }
            // skip IG-internal and example files, which live in other directories or are not resources.
            if ('.index.json' === $relativeName || !isset($json['resourceType'])) {
                continue;
            }
            $resources[] = $json;
        }

        if (null === $manifest) {
            throw new ProfilePackageException(sprintf(
                'Profile package source "%s" does not contain a "%s" file.',
                $source,
                self::_MANIFEST_FILENAME,
            ));
        }
        foreach (['name', 'version'] as $key) {
            if (!isset($manifest[$key]) || !is_string($manifest[$key]) || '' === $manifest[$key]) {
                throw new ProfilePackageException(sprintf(
                    'Profile package manifest in "%s" is missing required string field "%s".',
                    $source,
                    $key,
                ));
            }
        }
        if ($manifest['name'] !== $expectedName) {
            throw new ProfilePackageException(sprintf(
                'Profile package at "%s" is named "%s" but was configured as "%s".',
                $source,
                $manifest['name'],
                $expectedName,
            ));
        }

        $package = new ProfilePackage($config, $manifest);
        foreach ($resources as $resource) {
            $package->addResource($resource);
        }
        return $package;
    }

    /**
     * Yields the contents of the package manifest and every JSON file found in the package root, keyed by file name.
     *
     * @return \Generator<string, string>
     */
    private static function _iterateFiles(string $source): \Generator
    {
        if (is_dir($source)) {
            yield from self::_iterateDirectory($source);
        } else {
            yield from self::_iterateArchive($source);
        }
    }

    private static function _iterateDirectory(string $dir): \Generator
    {
        // extracted packages normally contain a "package" directory, but also allow pointing straight at it.
        $root = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . self::_PACKAGE_DIR;
        if (!is_dir($root)) {
            $root = $dir;
        }
        $files = glob($root . DIRECTORY_SEPARATOR . '*.json');
        if (false === $files) {
            throw new ProfilePackageException(sprintf('Unable to list files in "%s".', $root));
        }
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if (false === $contents) {
                throw new ProfilePackageException(sprintf('Unable to read "%s".', $file));
            }
            yield basename($file) => $contents;
        }
    }

    private static function _iterateArchive(string $archive): \Generator
    {
        if (!extension_loaded('phar') || !extension_loaded('zlib')) {
            throw new ProfilePackageException(
                'Reading profile package archives requires the "phar" and "zlib" PHP extensions.  Either enable them or point the package source at an extracted directory.'
            );
        }
        try {
            $phar = new \PharData($archive);
        } catch (\Throwable $e) {
            throw new ProfilePackageException(
                sprintf('Unable to open profile package archive "%s": %s', $archive, $e->getMessage()),
                0,
                $e,
            );
        }

        $prefix = 'phar://' . $phar->getPath() . '/' . self::_PACKAGE_DIR . '/';
        $files = [];
        foreach (new \RecursiveIteratorIterator($phar) as $entry) {
            /** @var \PharFileInfo $entry */
            $path = $entry->getPathname();
            if (!str_starts_with($path, $prefix)) {
                continue;
            }
            $name = substr($path, strlen($prefix));
            // only files directly inside the package directory are of interest.
            if (!str_contains($name, '/') && str_ends_with($name, '.json')) {
                $files[$name] = $path;
            }
        }
        ksort($files, SORT_STRING);
        foreach ($files as $name => $path) {
            $contents = file_get_contents($path);
            if (false === $contents) {
                throw new ProfilePackageException(sprintf('Unable to read "%s" from archive "%s".', $name, $archive));
            }
            yield $name => $contents;
        }
    }

    private static function _decode(string $contents, string $name, string $source): array
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException $e) {
            throw new ProfilePackageException(
                sprintf('Unable to decode "%s" in profile package "%s": %s', $name, $source, $e->getMessage()),
                0,
                $e,
            );
        }
        if (!is_array($decoded)) {
            throw new ProfilePackageException(sprintf(
                '"%s" in profile package "%s" is not a JSON object.',
                $name,
                $source,
            ));
        }
        return $decoded;
    }
}
