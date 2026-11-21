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
 * A CodeSystem, as far as enumerating its codes is concerned.
 */
final class ProfileCodeSystem
{
    /**
     * @param null|list<string> $codes Every code defined, nested concepts included; null if the resource lists no
     *                                 concepts.
     */
    public function __construct(
        public readonly string $id,
        public readonly null|string $url,
        public readonly bool $complete,
        public readonly null|array $codes,
    ) {
    }

    public static function fromResource(array $resource): self
    {
        $codes = null;
        if (isset($resource['concept']) && is_array($resource['concept'])) {
            $codes = [];
            self::_flatten($resource['concept'], $codes);
        }
        return new self(
            (string)($resource['id'] ?? ''),
            isset($resource['url']) && is_string($resource['url']) ? $resource['url'] : null,
            'complete' === ($resource['content'] ?? null),
            $codes,
        );
    }

    private static function _flatten(array $concepts, array &$codes): void
    {
        foreach ($concepts as $concept) {
            if (!is_array($concept)) {
                continue;
            }
            if (isset($concept['code']) && is_string($concept['code'])) {
                $codes[] = $concept['code'];
            }
            if (isset($concept['concept']) && is_array($concept['concept'])) {
                self::_flatten($concept['concept'], $codes);
            }
        }
    }
}
