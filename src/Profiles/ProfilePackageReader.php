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
use DCarbone\PHPFHIR\Enum\ProfileResourceTypeEnum;
use JsonException;
use PharData;
use PharFileInfo;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Reads FHIR NPM-style packages (https://hl7.org/fhir/packages.html) from either an extracted directory or a
 * .tgz archive.  No network access is performed.
 */
class ProfilePackageReader
{
    private const _MANIFEST_FILENAME = 'package.json';
    private const _INDEX_FILENAME = '.index.json';
    private const _PACKAGE_DIR = 'package';
    /** Conventional "<type>-<id>.json" names of files holding one of the retained resource types. */
    private const _RESOURCE_FILENAME_PATTERN = '{^(StructureDefinition|ValueSet|CodeSystem)-}';
    /** Cheap test for files that may hold one of the retained resource types, before they are decoded. */
    private const _RESOURCE_TYPE_PATTERN = '{"resourceType"\s*:\s*"(StructureDefinition|ValueSet|CodeSystem)"}';

    /**
     * @param ProfilePackageConfig $config
     * @param null|string $fhirVersion The FHIR version (e.g. "4.0.1") the package must support.  If null, no version
     *                                 compatibility check is performed.
     * @param ProfilePackage[] $dependencies Already read packages the package builds on.
     * @return ProfilePackage
     * @throws ProfilePackageException
     */
    public static function read(ProfilePackageConfig $config, null|string $fhirVersion = null, array $dependencies = []): ProfilePackage
    {
        $package = self::_readSource($config, $config->getSource(), $config->getName());

        if (null !== $fhirVersion) {
            self::_assertFHIRVersionCompatible($package, $fhirVersion);
        }

        foreach ($dependencies as $dependency) {
            $package->addLoadedDependency($dependency);
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
        $declaredList = '';
        foreach ($declared as $version) {
            $declaredList .= ('' === $declaredList ? '' : ', ') . $version;
        }
        throw new ProfilePackageException(sprintf(
            'Profile package "%s" supports FHIR version(s) [%s], which is not compatible with %s.',
            $package->getName(),
            $declaredList,
            $fhirVersion,
        ));
    }

    private static function _readSource(ProfilePackageConfig $config, string $source, string $expectedName): ProfilePackage
    {
        $files = self::_listFiles($source);
        if (!isset($files[self::_MANIFEST_FILENAME])) {
            throw new ProfilePackageException(sprintf(
                'Profile package source "%s" does not contain a "%s" file.',
                $source,
                self::_MANIFEST_FILENAME,
            ));
        }
        $manifest = self::_decode(self::_readFile($files[self::_MANIFEST_FILENAME], $source), self::_MANIFEST_FILENAME, $source);
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

        $indexed = isset($files[self::_INDEX_FILENAME])
            ? self::_indexedTypes(self::_decode(self::_readFile($files[self::_INDEX_FILENAME], $source), self::_INDEX_FILENAME, $source))
            : [];

        $resources = [];
        foreach ($files as $name => $path) {
            if (self::_MANIFEST_FILENAME === $name || self::_INDEX_FILENAME === $name) {
                continue;
            }
            // packages can be huge (e.g. terminology), so only the resource types that are used are decoded.  File names
            // are not standardised: besides the conventional "<type>-<id>.json" names, the package index says what a
            // file holds, and files it does not list are checked for a resource type of interest before being decoded.
            $conventional = 1 === preg_match(self::_RESOURCE_FILENAME_PATTERN, $name);
            if (!$conventional && isset($indexed[$name]) && null === ProfileResourceTypeEnum::tryFrom($indexed[$name])) {
                continue;
            }
            $contents = self::_readFile($path, $source);
            if (!$conventional && !isset($indexed[$name]) && 1 !== preg_match(self::_RESOURCE_TYPE_PATTERN, $contents)) {
                continue;
            }
            $json = self::_decode($contents, $name, $source);
            unset($contents);
            if (!isset($json['resourceType']) || !is_string($json['resourceType']) || null === ProfileResourceTypeEnum::tryFrom($json['resourceType'])) {
                continue;
            }
            // narrative is never used and is a large share of big packages.
            unset($json['text']);
            if (null !== ($packed = ProfilePackage::packResource($json))) {
                $resources[] = $packed;
            }
        }

        $package = new ProfilePackage($config, $manifest);
        foreach ($resources as $resource) {
            $package->addPackedResource($resource);
        }
        return $package;
    }

    /**
     * @return array<string, string> package index file name => resource type, for the files the index lists.
     */
    private static function _indexedTypes(array $index): array
    {
        $out = [];
        foreach ($index['files'] ?? [] as $entry) {
            if (is_array($entry) && isset($entry['filename'], $entry['resourceType']) && is_string($entry['filename']) && is_string($entry['resourceType'])) {
                $out[$entry['filename']] = $entry['resourceType'];
            }
        }
        return $out;
    }

    /**
     * Lists the JSON files directly inside the package directory.
     *
     * @return array<string, string> file name => path, sorted by name.
     */
    private static function _listFiles(string $source): array
    {
        $files = is_dir($source) ? self::_listDirectory($source) : self::_listArchive($source);
        ksort($files, SORT_STRING);
        return $files;
    }

    /** @return array<string, string> */
    private static function _listDirectory(string $dir): array
    {
        // extracted packages normally contain a "package" directory, but also allow pointing straight at it.
        $root = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . self::_PACKAGE_DIR;
        if (!is_dir($root)) {
            $root = $dir;
        }
        // scandir rather than glob: it sees dot files (such as the package index) and is unaffected by glob
        // metacharacters in the directory path.
        $entries = @scandir($root, SCANDIR_SORT_NONE);
        if (false === $entries) {
            throw new ProfilePackageException(sprintf('Unable to list files in "%s".', $root));
        }
        $files = [];
        foreach ($entries as $entry) {
            if (0 !== strcasecmp(substr($entry, -5), '.json')) {
                continue;
            }
            $path = $root . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                $files[$entry] = $path;
            }
        }
        return $files;
    }

    /** @return array<string, string> */
    private static function _listArchive(string $archive): array
    {
        if (!extension_loaded('phar') || !extension_loaded('zlib')) {
            throw new ProfilePackageException(
                'Reading profile package archives requires the "phar" and "zlib" PHP extensions.  Either enable them or point the package source at an extracted directory.'
            );
        }
        try {
            $phar = new PharData($archive);
        } catch (Throwable $e) {
            throw new ProfilePackageException(
                sprintf('Unable to open profile package archive "%s": %s', $archive, $e->getMessage()),
                0,
                $e,
            );
        }

        $prefix = 'phar://' . $phar->getPath() . '/' . self::_PACKAGE_DIR . '/';
        $files = [];
        foreach (new RecursiveIteratorIterator($phar) as $entry) {
            /** @var PharFileInfo $entry */
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
        return $files;
    }

    private static function _readFile(string $path, string $source): string
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new ProfilePackageException(sprintf('Unable to read "%s" from profile package "%s".', $path, $source));
        }
        return $contents;
    }

    private static function _decode(string $contents, string $name, string $source): array
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException $e) {
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
