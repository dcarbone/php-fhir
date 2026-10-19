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
 * package, etc. - are reported as not expandable, rather than guessed at.  A ValueSet's own expansion is used when it is
 * complete; a paged one is ignored in favour of the compose definition.
 */
class ValueSetExpander
{
    private const _MAX_DEPTH = 10;
    private readonly ProfilePackage $package;

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
        $codes = $this->_expand($canonical, 0);
        return [] === $codes ? null : $codes;
    }

    private function _expand(string $canonical, int $depth): null|array
    {
        if ($depth > self::_MAX_DEPTH) {
            return null;
        }
        $vs = $this->package->resolveValueSet($canonical);
        if (null === $vs) {
            return null;
        }

        if (isset($vs['expansion']['contains']) && is_array($vs['expansion']['contains'])) {
            $out = [];
            $entries = self::_flattenContains($vs['expansion']['contains'], $out);
            if (self::_expansionIsComplete($vs['expansion'], $entries)) {
                return self::_unique($out);
            }
        }

        if (!isset($vs['compose']['include']) || !is_array($vs['compose']['include'])) {
            return null;
        }

        $out = [];
        foreach ($vs['compose']['include'] as $include) {
            $codes = $this->_expandInclude($include, $depth);
            if (null === $codes) {
                return null;
            }
            array_push($out, ...$codes);
        }

        foreach ($vs['compose']['exclude'] ?? [] as $exclude) {
            $remove = $this->_expandInclude($exclude, $depth);
            if (null === $remove) {
                return null;
            }
            $removeKeys = [];
            foreach ($remove as $p) {
                $removeKeys[$p[0] . '|' . $p[1]] = true;
            }
            $kept = [];
            foreach ($out as $p) {
                if (!isset($removeKeys[$p[0] . '|' . $p[1]])) {
                    $kept[] = $p;
                }
            }
            $out = $kept;
        }

        return self::_unique($out);
    }

    private function _expandInclude(array $include, int $depth): null|array
    {
        if (isset($include['filter'])) {
            return null;
        }
        $system = $include['system'] ?? null;
        $valueSets = $include['valueSet'] ?? [];

        if (null !== $system && [] !== $valueSets) {
            return null;
        }

        if (null !== $system) {
            if (isset($include['concept']) && is_array($include['concept'])) {
                $out = [];
                foreach ($include['concept'] as $concept) {
                    if (!isset($concept['code']) || !is_string($concept['code'])) {
                        return null;
                    }
                    $out[] = [$system, $concept['code']];
                }
                return $out;
            }
            $cs = $this->package->resolveCodeSystem($system);
            if (null === $cs || 'complete' !== ($cs['content'] ?? null) || !isset($cs['concept'])) {
                return null;
            }
            $out = [];
            self::_flattenConcepts($system, $cs['concept'], $out);
            return $out;
        }

        if ([] === $valueSets) {
            return null;
        }

        // multiple valueSet references in one include are an intersection.
        $result = null;
        foreach ($valueSets as $ref) {
            $expanded = $this->_expand((string)$ref, $depth + 1);
            if (null === $expanded) {
                return null;
            }
            if (null === $result) {
                $result = $expanded;
                continue;
            }
            $keys = [];
            foreach ($expanded as $p) {
                $keys[$p[0] . '|' . $p[1]] = true;
            }
            $kept = [];
            foreach ($result as $p) {
                if (isset($keys[$p[0] . '|' . $p[1]])) {
                    $kept[] = $p;
                }
            }
            $result = $kept;
        }
        return $result;
    }

    /**
     * An expansion may be a single page of a larger one.  It only stands for the whole ValueSet when it starts at the
     * beginning and lists at least as many entries as its declared total.
     *
     * @param array $expansion
     * @param int $entries Number of entries listed in the expansion, including abstract and nested ones.
     * @return bool
     */
    private static function _expansionIsComplete(array $expansion, int $entries): bool
    {
        if (0 !== (int)($expansion['offset'] ?? 0)) {
            return false;
        }
        return !isset($expansion['total']) || (int)$expansion['total'] <= $entries;
    }

    /**
     * @param array $contains
     * @param array $out Receives the [system, code] pair of every non-abstract entry.
     * @return int Number of entries seen, including abstract and nested ones.
     */
    private static function _flattenContains(array $contains, array &$out): int
    {
        $entries = 0;
        foreach ($contains as $item) {
            $entries++;
            if (isset($item['code']) && is_string($item['code']) && !($item['abstract'] ?? false)) {
                $out[] = [$item['system'] ?? null, $item['code']];
            }
            if (isset($item['contains']) && is_array($item['contains'])) {
                $entries += self::_flattenContains($item['contains'], $out);
            }
        }
        return $entries;
    }

    private static function _flattenConcepts(string $system, array $concepts, array &$out): void
    {
        foreach ($concepts as $concept) {
            if (isset($concept['code']) && is_string($concept['code'])) {
                $out[] = [$system, $concept['code']];
            }
            if (isset($concept['concept']) && is_array($concept['concept'])) {
                self::_flattenConcepts($system, $concept['concept'], $out);
            }
        }
    }

    private static function _unique(array $pairs): array
    {
        $seen = [];
        $out = [];
        foreach ($pairs as $pair) {
            $key = $pair[0] . '|' . $pair[1];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $pair;
            }
        }
        return $out;
    }
}
