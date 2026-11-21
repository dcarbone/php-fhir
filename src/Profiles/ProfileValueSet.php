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
 * A ValueSet, as far as enumerating its codes is concerned.
 */
final class ProfileValueSet
{
    /**
     * @param null|list<ProfileValueSetInclude> $includes Null if the resource has no usable "compose.include".
     * @param list<ProfileValueSetInclude> $excludes
     */
    public function __construct(
        public readonly string $id,
        public readonly null|string $url,
        public readonly null|ProfileValueSetExpansion $expansion,
        public readonly null|array $includes,
        public readonly array $excludes,
    ) {
    }

    public static function fromResource(array $resource): self
    {
        $compose = isset($resource['compose']) && is_array($resource['compose']) ? $resource['compose'] : [];

        $includes = null;
        if (isset($compose['include']) && is_array($compose['include'])) {
            $includes = array_map(ProfileValueSetInclude::fromResource(...), array_values($compose['include']));
        }
        $excludes = $compose['exclude'] ?? [];
        // a malformed exclusion is carried as one that cannot be listed, rather than ignored.
        $excludes = array_map(ProfileValueSetInclude::fromResource(...), is_array($excludes) ? array_values($excludes) : [null]);

        return new self(
            (string)($resource['id'] ?? ''),
            isset($resource['url']) && is_string($resource['url']) ? $resource['url'] : null,
            isset($resource['expansion']) && is_array($resource['expansion']) ? ProfileValueSetExpansion::fromResource($resource['expansion']) : null,
            $includes,
            $excludes,
        );
    }
}
