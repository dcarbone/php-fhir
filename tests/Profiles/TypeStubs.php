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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;

/**
 * Builds generated type definitions for the profile tests to resolve base types to.
 */
final class TypeStubs
{
    /**
     * @param string $libraryNamespacePrefix Types then live beneath "{prefix}\Versions\R4\Types".
     * @param string ...$parents FHIR names of the ancestors every resolved type has, outermost first.
     * @return \Closure(string): Type
     */
    public static function resolver(string $libraryNamespacePrefix, string ...$parents): \Closure
    {
        $dir = sys_get_temp_dir();
        $version = new Version(
            new Config(libraryPath: $dir, versions: [], libraryNamespacePrefix: $libraryNamespacePrefix),
            new VersionConfig('R4', $dir),
        );
        $parent = null;
        foreach ($parents as $name) {
            $parent = (new Type($version, $name))->setParentType($parent);
        }
        return static fn(string $fhirName): Type => (new Type($version, $fhirName))->setParentType($parent);
    }
}
