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
 * package, etc. - are reported as not expandable, rather than guessed at.
 */
class ValueSetExpander
{
    private const _MAX_DEPTH = 10;

    public function __construct(private readonly ProfilePackage $package)
    {
    }

    /**
     * @param string $canonical ValueSet canonical url, optionally with a "|version" suffix.
     * @return null|array<int, array{0: null|string, 1: string}> [system, code] pairs, or null if the ValueSet is
     *         not available or cannot be fully enumerated.
     */
    public function expand(string $canonical): null|array
    {
        return $this->_expand($canonical, 0);
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
            self::_flattenContains($vs['expansion']['contains'], $out);
            return self::_unique($out);
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
            $removeKeys = array_map(static fn(array $p): string => $p[0] . '|' . $p[1], $remove);
            $out = array_filter($out, static fn(array $p): bool => !in_array($p[0] . '|' . $p[1], $removeKeys, true));
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
            $keys = array_map(static fn(array $p): string => $p[0] . '|' . $p[1], $expanded);
            $result = array_values(array_filter(
                $result,
                static fn(array $p): bool => in_array($p[0] . '|' . $p[1], $keys, true),
            ));
        }
        return $result;
    }

    private static function _flattenContains(array $contains, array &$out): void
    {
        foreach ($contains as $item) {
            if (isset($item['code']) && is_string($item['code']) && !($item['abstract'] ?? false)) {
                $out[] = [$item['system'] ?? null, $item['code']];
            }
            if (isset($item['contains']) && is_array($item['contains'])) {
                self::_flattenContains($item['contains'], $out);
            }
        }
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
