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
 * Expands ValueSets to a flat list of codes using only what is available in a profile package (and its loaded
 * dependencies).  ValueSets that cannot be fully enumerated that way - filters, code systems that are not in the
 * package, etc. - are reported as not expandable, rather than guessed at.  A ValueSet's own expansion is used only when
 * it can be shown to be complete; a paged or truncated one is ignored in favour of the compose definition.
 */
class ValueSetExpander
{
    private readonly ProfilePackage $package;
    /** @var array<string, null|array> raw results of requested expansions keyed by versionless canonical */
    private array $_cache = [];
    /** @var array<string, null|array> included expansions completed during the current request, shared ones only */
    private array $_included = [];
    /** @var array<string, true> included ValueSets expanded at least once during the current request */
    private array $_seen = [];
    /** @var array<string, true> canonicals currently being expanded, used to break reference cycles */
    private array $_expanding = [];

    public function __construct(ProfilePackage $package)
    {
        $this->package = $package;
    }

    /**
     * @param string $canonical ValueSet canonical url, optionally with a "|version" suffix.
     * @return null|array<int, array{0: null|string, 1: string}> [system, code] pairs, or null if the ValueSet is
     *         not available, cannot be fully enumerated, or enumerates to nothing.  A binding to no codes at all
     *         would reject every value, which is far more likely a gap in the package than the intent.
     */
    public function expand(string $canonical): null|array
    {
        $key = ProfilePackage::stripCanonicalVersion($canonical);
        if (!array_key_exists($key, $this->_cache)) {
            try {
                $this->_cache[$key] = $this->_expand($canonical);
            } finally {
                // included expansions are memoized only for the duration of a request, and only once they prove
                // to be shared: an include chain then does not retain the codes of every level, while a ValueSet
                // included from several places is expanded at most twice.
                $this->_included = [];
                $this->_seen = [];
            }
        }
        return [] === $this->_cache[$key] ? null : $this->_cache[$key];
    }

    private function _expand(string $canonical): null|array
    {
        $key = ProfilePackage::stripCanonicalVersion($canonical);
        if (array_key_exists($key, $this->_cache)) {
            return $this->_cache[$key];
        }
        if (array_key_exists($key, $this->_included)) {
            return $this->_included[$key];
        }
        if (isset($this->_expanding[$key])) {
            // a ValueSet that (transitively) includes itself cannot be enumerated.
            return null;
        }
        $this->_expanding[$key] = true;
        try {
            $codes = $this->_doExpand($canonical);
        } finally {
            unset($this->_expanding[$key]);
        }
        if (isset($this->_seen[$key])) {
            $this->_included[$key] = $codes;
        } else {
            $this->_seen[$key] = true;
        }
        return $codes;
    }

    private function _doExpand(string $canonical): null|array
    {
        $vs = $this->package->resolveValueSet($canonical);
        if (null === $vs) {
            return null;
        }

        if (null !== $vs->expansion && $vs->expansion->complete) {
            return self::_unique($vs->expansion->codes);
        }

        if (null === $vs->includes) {
            return null;
        }

        $out = [];
        foreach ($vs->includes as $include) {
            $codes = $this->_expandInclude($include);
            if (null === $codes) {
                return null;
            }
            array_push($out, ...$codes);
        }

        foreach ($vs->excludes as $exclude) {
            $remove = $this->_expandInclude($exclude);
            if (null === $remove) {
                return null;
            }
            $removeKeys = self::_keys($remove);
            $out = array_values(array_filter($out, static fn(array $p) => !isset($removeKeys[self::_key($p)])));
        }

        return self::_unique($out);
    }

    private function _expandInclude(ProfileValueSetInclude $include): null|array
    {
        if (!$include->enumerable) {
            return null;
        }
        $system = $include->system;
        if (null !== $system && [] !== $include->valueSets) {
            return null;
        }

        if (null !== $system) {
            $codes = $include->concepts;
            if (null === $codes) {
                $cs = $this->package->resolveCodeSystem($system);
                if (null === $cs || !$cs->complete || null === $cs->codes) {
                    return null;
                }
                $codes = $cs->codes;
            }
            return array_map(static fn(string $code) => [$system, $code], $codes);
        }

        if ([] === $include->valueSets) {
            return null;
        }

        // multiple valueSet references in one include are an intersection.
        $result = null;
        foreach ($include->valueSets as $ref) {
            $expanded = $this->_expand($ref);
            if (null === $expanded) {
                return null;
            }
            if (null === $result) {
                $result = $expanded;
                continue;
            }
            $keys = self::_keys($expanded);
            $result = array_values(array_filter($result, static fn(array $p) => isset($keys[self::_key($p)])));
        }
        return $result;
    }

    /**
     * @param array{0: null|string, 1: string} $pair
     */
    private static function _key(array $pair): string
    {
        return $pair[0] . '|' . $pair[1];
    }

    /**
     * @param array<int, array{0: null|string, 1: string}> $pairs
     * @return array<string, true>
     */
    private static function _keys(array $pairs): array
    {
        $keys = [];
        foreach ($pairs as $pair) {
            $keys[self::_key($pair)] = true;
        }
        return $keys;
    }

    private static function _unique(array $pairs): array
    {
        $seen = [];
        $out = [];
        foreach ($pairs as $pair) {
            $key = self::_key($pair);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $pair;
            }
        }
        return $out;
    }
}
