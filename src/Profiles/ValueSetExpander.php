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
    private const _MAX_DEPTH = 10;
    // expansion extensions stating that the expansion does not hold every code of the ValueSet.
    private const _PARTIAL_EXPANSION_EXTENSIONS = [
        'http://hl7.org/fhir/StructureDefinition/valueset-toocostly',
        'http://hl7.org/fhir/StructureDefinition/valueset-unclosed',
    ];
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
            $listed = [];
            self::_flattenContains($vs['expansion']['contains'], $out, $listed);
            if (self::_expansionIsComplete($vs['expansion'], count($listed))) {
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
     * An expansion may be a single page of a larger one, or cut short.  It only stands for the whole ValueSet when it
     * starts at the beginning, is not marked as partial, and the distinct codes it lists account for its declared total.
     * Paging requested through the "count" parameter without a total cannot be shown to be complete.  Anything
     * malformed counts as incomplete.
     *
     * @param array $expansion
     * @param int $codes Number of distinct codes listed in the expansion, abstract and nested ones included.
     * @return bool
     */
    private static function _expansionIsComplete(array $expansion, int $codes): bool
    {
        if (isset($expansion['offset']) && 0 !== $expansion['offset']) {
            return false;
        }
        foreach ($expansion['extension'] ?? [] as $extension) {
            if (in_array($extension['url'] ?? null, self::_PARTIAL_EXPANSION_EXTENSIONS, true)) {
                return false;
            }
        }
        $paged = false;
        foreach ($expansion['parameter'] ?? [] as $parameter) {
            $name = $parameter['name'] ?? null;
            if ('offset' === $name && 0 !== ($parameter['valueInteger'] ?? null)) {
                return false;
            }
            if ('count' === $name) {
                $paged = true;
            }
        }
        if (!isset($expansion['total'])) {
            return !$paged;
        }
        return is_int($expansion['total']) && $expansion['total'] <= $codes;
    }

    /**
     * @param array $contains
     * @param array $out Receives the [system, code] pair of every non-abstract entry.
     * @param array $listed Receives a key for every distinct code listed, abstract ones included.
     */
    private static function _flattenContains(array $contains, array &$out, array &$listed): void
    {
        foreach ($contains as $item) {
            if (isset($item['code']) && is_string($item['code'])) {
                $listed[($item['system'] ?? '') . '|' . $item['code']] = true;
                if (!($item['abstract'] ?? false)) {
                    $out[] = [$item['system'] ?? null, $item['code']];
                }
            }
            if (isset($item['contains']) && is_array($item['contains'])) {
                self::_flattenContains($item['contains'], $out, $listed);
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
