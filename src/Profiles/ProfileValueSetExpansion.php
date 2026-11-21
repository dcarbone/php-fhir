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
 * The expansion a ValueSet carries.
 *
 * An expansion may be a single page of a larger one, or cut short.  It only stands for the whole ValueSet when it
 * starts at the beginning, is not marked as partial, and the distinct codes it lists account for its declared total.
 * Paging requested through the "count" parameter without a total cannot be shown to be complete.  Anything malformed
 * counts as incomplete.
 */
final class ProfileValueSetExpansion
{
    // expansion extensions stating that the expansion does not hold every code of the ValueSet.
    private const _PARTIAL_EXTENSIONS = [
        'http://hl7.org/fhir/StructureDefinition/valueset-toocostly',
        'http://hl7.org/fhir/StructureDefinition/valueset-unclosed',
    ];

    /**
     * @param list<array{0: null|string, 1: string}> $codes [system, code] of every non-abstract entry, nested ones
     *                                                       included.
     */
    public function __construct(
        public readonly array $codes,
        public readonly bool $complete,
    ) {
    }

    public static function fromResource(array $expansion): null|self
    {
        if (!isset($expansion['contains']) || !is_array($expansion['contains'])) {
            return null;
        }
        $codes = [];
        $listed = [];
        self::_flatten($expansion['contains'], $codes, $listed);
        return new self($codes, self::_isComplete($expansion, count($listed)));
    }

    /**
     * @param int $listed Number of distinct codes listed, abstract and nested ones included.
     */
    private static function _isComplete(array $expansion, int $listed): bool
    {
        if (isset($expansion['offset']) && 0 !== $expansion['offset']) {
            return false;
        }
        foreach ($expansion['extension'] ?? [] as $extension) {
            if (is_array($extension) && in_array($extension['url'] ?? null, self::_PARTIAL_EXTENSIONS, true)) {
                return false;
            }
        }
        $paged = false;
        foreach ($expansion['parameter'] ?? [] as $parameter) {
            $name = is_array($parameter) ? ($parameter['name'] ?? null) : null;
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
        return is_int($expansion['total']) && $expansion['total'] <= $listed;
    }

    private static function _flatten(array $contains, array &$codes, array &$listed): void
    {
        foreach ($contains as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['code']) && is_string($item['code'])) {
                $system = isset($item['system']) && is_string($item['system']) ? $item['system'] : null;
                $listed[($system ?? '') . '|' . $item['code']] = true;
                if (!($item['abstract'] ?? false)) {
                    $codes[] = [$system, $item['code']];
                }
            }
            if (isset($item['contains']) && is_array($item['contains'])) {
                self::_flatten($item['contains'], $codes, $listed);
            }
        }
    }
}
