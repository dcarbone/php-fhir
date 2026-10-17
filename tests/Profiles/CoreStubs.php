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
 * Stand-ins for the generated core enums and interfaces that the generated profile support code depends on.
 */
final class CoreStubs
{
    public const NAMESPACE = 'Acme\\Gen\\Encoding';
    public const TYPES_NAMESPACE = 'Acme\\Gen\\Types';

    public const ON_UNKNOWN_PROFILE = 'OnUnknownProfileEnum';
    public const ON_AMBIGUOUS_PROFILE = 'OnAmbiguousProfileEnum';
    public const ON_PROFILE_VERSION_MISMATCH = 'OnProfileVersionMismatchEnum';
    public const PROFILE_TYPE_INTERFACE = 'ProfileTypeInterface';

    /**
     * Defines the stand-ins, if they are not defined yet.
     *
     * @return array<string, string> Fully qualified name of each stand-in, by entity name.
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
        $out[self::PROFILE_TYPE_INTERFACE] = self::TYPES_NAMESPACE . '\\' . self::PROFILE_TYPE_INTERFACE;
        if (!interface_exists($out[self::PROFILE_TYPE_INTERFACE], false)) {
            eval(sprintf(
                'namespace %s; interface %s { public static function _getProfileUrl(): string; public static function _getProfileVersion(): null|string; public function _getProfileValidationErrors(): array; }',
                self::TYPES_NAMESPACE,
                self::PROFILE_TYPE_INTERFACE,
            ));
        }
        return $out;
    }
}
