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
 * A ConceptMap, as far as mapping one code to another is concerned.
 */
final class ProfileConceptMap
{
    /**
     * @param list<ProfileConceptMapGroup> $groups
     */
    public function __construct(
        public readonly string $id,
        public readonly array $groups,
    ) {
    }

    /**
     * @param array<string, mixed> $resource
     * @throws ProfileDefinitionException If the structure or code-system version metadata is malformed.
     */
    public static function fromResource(array $resource): self
    {
        $id = (string)($resource['id'] ?? '');
        $rawGroups = $resource['group'] ?? [];
        if (!is_array($rawGroups)) {
            throw new ProfileDefinitionException(sprintf('ConceptMap "%s" has a non-array "group" value.', $id));
        }
        $groups = [];
        foreach ($rawGroups as $rawGroup) {
            // a group that does not map one code system to another has nothing to offer.
            if (!is_array($rawGroup)
                || !isset($rawGroup['source'], $rawGroup['target'])
                || !is_string($rawGroup['source'])
                || !is_string($rawGroup['target'])
                || '' === $rawGroup['source']
                || '' === $rawGroup['target']) {
                continue;
            }
            $source = explode('|', $rawGroup['source'], 2);
            $target = explode('|', $rawGroup['target'], 2);
            if ('' === $source[0] || '' === $target[0]) {
                throw new ProfileDefinitionException(sprintf('ConceptMap "%s" has an empty code-system URI.', $id));
            }
            $groups[] = new ProfileConceptMapGroup(
                $source[0],
                $target[0],
                self::_elements($id, $rawGroup['element'] ?? []),
                self::_version($id, 'sourceVersion', $rawGroup['sourceVersion'] ?? null, $source[1] ?? null),
                self::_version($id, 'targetVersion', $rawGroup['targetVersion'] ?? null, $target[1] ?? null),
            );
        }
        return new self($id, $groups);
    }

    private static function _version(
        string $id,
        string $field,
        mixed $explicitVersion,
        null|string $canonicalVersion,
    ): null|string {
        if ((null !== $explicitVersion && (!is_string($explicitVersion) || '' === $explicitVersion))
            || '' === $canonicalVersion) {
            throw new ProfileDefinitionException(sprintf('ConceptMap "%s" has an invalid "%s".', $id, $field));
        }
        if (null !== $explicitVersion && null !== $canonicalVersion && $explicitVersion !== $canonicalVersion) {
            throw new ProfileDefinitionException(sprintf('ConceptMap "%s" has conflicting "%s" values.', $id, $field));
        }
        return $explicitVersion ?? $canonicalVersion;
    }

    /**
     * @return list<ProfileConceptMapElement>
     */
    private static function _elements(string $id, mixed $rawElements): array
    {
        if (!is_array($rawElements)) {
            throw new ProfileDefinitionException(sprintf('ConceptMap "%s" has a non-array group "element" value.', $id));
        }
        $elements = [];
        foreach ($rawElements as $rawElement) {
            if (!is_array($rawElement) || !isset($rawElement['code']) || !is_string($rawElement['code'])) {
                throw new ProfileDefinitionException(sprintf(
                    'ConceptMap "%s" contains an element without a string source code.',
                    $id,
                ));
            }
            $rawTargets = $rawElement['target'] ?? [];
            if (!is_array($rawTargets)) {
                throw new ProfileDefinitionException(sprintf('ConceptMap "%s" contains a non-array target list.', $id));
            }
            $equivalent = [];
            foreach ($rawTargets as $target) {
                if (!is_array($target) || !isset($target['code']) || !is_string($target['code'])) {
                    continue;
                }
                if ([] !== ($target['dependsOn'] ?? []) || [] !== ($target['product'] ?? [])) {
                    continue;
                }
                // R4 distinguishes exact equality; R5 folds it into equivalent.
                $relationship = $target['relationship'] ?? null;
                if ('equivalent' === $relationship
                    || (null === $relationship && in_array($target['equivalence'] ?? null, ['equal', 'equivalent'], true))) {
                    $equivalent[$target['code']] = true;
                }
            }
            // numeric codes become integer array keys, so restore them to strings.
            $elements[] = new ProfileConceptMapElement($rawElement['code'], array_map('strval', array_keys($equivalent)));
        }
        return $elements;
    }
}
