<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Profiles;

/*
 * Copyright 2025-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Helpers for building small FHIR NPM-style packages on disk for tests.
 */
trait PackageFixtures
{
    protected static function manifest(string $name = 'test.ig', array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'version' => '1.0.0',
            'canonical' => 'http://example.org/fhir/test',
            'fhirVersions' => ['4.0.1'],
            'dependencies' => ['hl7.fhir.r4.core' => '4.0.1'],
        ], $overrides);
    }

    protected static function structureDefinition(string $id,
                                                  string $type = 'Patient',
                                                  string $derivation = 'constraint',
                                                  array  $overrides = []): array
    {
        return array_merge([
            'resourceType' => 'StructureDefinition',
            'id' => $id,
            'url' => 'http://example.org/fhir/StructureDefinition/' . $id,
            'name' => str_replace('-', '', ucwords($id, '-')),
            'kind' => 'resource',
            'abstract' => false,
            'type' => $type,
            'baseDefinition' => 'http://hl7.org/fhir/StructureDefinition/' . $type,
            'derivation' => $derivation,
            'differential' => ['element' => []],
        ], $overrides);
    }

    protected static function valueSet(string $id): array
    {
        return [
            'resourceType' => 'ValueSet',
            'id' => $id,
            'url' => 'http://example.org/fhir/ValueSet/' . $id,
            'status' => 'active',
        ];
    }

    protected static function codeSystem(string $id): array
    {
        return [
            'resourceType' => 'CodeSystem',
            'id' => $id,
            'url' => 'http://example.org/fhir/CodeSystem/' . $id,
            'status' => 'active',
            'content' => 'complete',
        ];
    }

    /**
     * @param array<string, array|string> $files File name => decoded JSON (array) or raw contents (string).
     * @return array<string, string>
     */
    protected static function encodeFiles(array $files): array
    {
        $out = [];
        foreach ($files as $name => $contents) {
            $out[$name] = is_string($contents) ? $contents : json_encode($contents, JSON_THROW_ON_ERROR);
        }
        return $out;
    }

    /**
     * Writes an extracted package directory.
     *
     * @param string $path Directory to create.
     * @param array<string, array|string> $files
     * @param bool $withPackageDir If true, files are written to $path/package, matching the layout of extracted tgz.
     */
    protected static function writePackageDir(string $path, array $files, bool $withPackageDir = true): string
    {
        $root = $withPackageDir ? $path . '/package' : $path;
        mkdir($root, 0777, true);
        foreach (self::encodeFiles($files) as $name => $contents) {
            file_put_contents($root . '/' . $name, $contents);
        }
        return $path;
    }

    /**
     * Writes a package .tgz archive.
     *
     * @param string $path Full path of the .tgz file to create.
     * @param array<string, array|string> $files Names are relative to the "package/" dir inside the archive, unless
     *                                          they start with "/", in which case the leading slash is stripped and
     *                                          the file is placed at the archive root.
     */
    protected static function writePackageArchive(string $path, array $files): string
    {
        $tar = preg_replace('{\.tgz$}', '.tar', $path);
        $phar = new \PharData($tar);
        foreach (self::encodeFiles($files) as $name => $contents) {
            $entry = str_starts_with($name, '/') ? ltrim($name, '/') : 'package/' . $name;
            $phar->addFromString($entry, $contents);
        }
        $phar->compress(\Phar::GZ);
        unset($phar);
        \Phar::unlinkArchive($tar);
        rename($tar . '.gz', $path);
        return $path;
    }
}
