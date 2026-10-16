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
 * Stand-ins for the generated core option enums that the generated profile code depends on.
 */
final class CoreEnumStubs
{
    public const NAMESPACE = 'Acme\\Gen\\Encoding';

    public const ON_UNKNOWN_PROFILE = 'OnUnknownProfileEnum';
    public const ON_AMBIGUOUS_PROFILE = 'OnAmbiguousProfileEnum';
    public const ON_PROFILE_VERSION_MISMATCH = 'OnProfileVersionMismatchEnum';

    /**
     * Defines the enums, if they are not defined yet.
     *
     * @return array<string, string> Fully qualified name of each enum, by entity name.
     */
    public static function define(): array
    {
        $enums = [
            self::ON_UNKNOWN_PROFILE => "case FALLBACK = 'fallback'; case EXCEPTION = 'exception';",
            self::ON_AMBIGUOUS_PROFILE => "case FIRST_DECLARED = 'firstDeclared'; case FALLBACK = 'fallback'; case EXCEPTION = 'exception';",
            self::ON_PROFILE_VERSION_MISMATCH => "case IGNORE_VERSION = 'ignoreVersion'; case FALLBACK = 'fallback'; case EXCEPTION = 'exception';",
        ];
        $out = [];
        foreach ($enums as $name => $cases) {
            $out[$name] = self::NAMESPACE . '\\' . $name;
            if (!enum_exists($out[$name], false)) {
                eval(sprintf('namespace %s; enum %s: string { %s }', self::NAMESPACE, $name, $cases));
            }
        }
        return $out;
    }
}
