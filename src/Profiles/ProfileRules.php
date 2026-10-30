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
 * Compiles a {@see \DCarbone\PHPFHIR\Profiles\Profile} down to the plain-array rules that generated profile classes
 * validate against.
 *
 * Only elements the profile, or one of its parent profiles, actually touches produce rules.  Values come from the
 * snapshot when the profile has one, so inherited constraints are already merged.
 *
 * Rules are evaluated against the FHIR JSON form of a resource.  Not covered: constraints on elements inside a
 * slice, slice discriminators other than "value" / "pattern" on plain element paths, closed slicing, and invariants,
 * which are only carried along as metadata.  Slices without discriminators are matched on all of their fixed / pattern
 * values.
 *
 * @phpstan-type Rules array{
 *     rules: array<int, array<string, mixed>>,
 *     slices: array<int, array<string, mixed>>,
 *     invariants: array<int, array<string, string|null>>,
 *     skipped: string[]
 * }
 */
class ProfileRules
{
    /** "$this", or a dotted path of plain element names.  Functions such as resolve() or extension() are not supported. */
    private const _DISCRIMINATOR_PATH_PATTERN = '{^(\$this|[A-Za-z][A-Za-z0-9]*(\.[A-Za-z][A-Za-z0-9]*)*)$}';

    /**
     * @return array{rules: array<int, array<string, mixed>>, slices: array<int, array<string, mixed>>, invariants: array<int, array<string, null|string>>, skipped: string[]}
     */
    public static function compile(Profile $profile, null|ValueSetExpander $expander = null): array
    {
        $chain = [$profile];
        foreach ($profile->getAncestors() as $ancestor) {
            $chain[] = $ancestor;
        }

        $ids = [];
        foreach ($chain as $p) {
            foreach (array_keys($p->getDifferentialElements()) as $id) {
                $ids[$id] = true;
            }
        }

        $rules = [];
        $slices = [];
        $invariants = [];
        $skipped = [];
        $sliceChildren = null;

        foreach (array_keys($ids) as $id) {
            $element = self::_effective($profile, $chain, $id);
            if (null === $element || $element->isRoot()) {
                continue;
            }

            foreach (self::_differentialConstraints($chain, $id) as $constraint) {
                $invariants[] = [
                    'key' => $constraint->getKey(),
                    'severity' => $constraint->getSeverity(),
                    'human' => $constraint->getHuman(),
                    'expression' => $constraint->getExpression(),
                    'path' => self::_relativePath($element),
                ];
            }

            if ($element->isSlice() && 1 === substr_count($id, ':') && !str_contains(substr($id, (int)strrpos($id, ':')), '.')) {
                $sliceChildren ??= self::_sliceChildren($profile, $chain);
                $slice = self::_compileSlice($profile, $chain, $element, $sliceChildren[$id] ?? []);
                if (null === $slice) {
                    $skipped[] = sprintf('%s: slice cannot be matched (no fixed / pattern values or extension profile)', $id);
                } else {
                    $slices[] = $slice;
                }
                continue;
            }
            if ($element->isWithinSlice()) {
                continue;
            }

            $rule = self::_compileElement($element, $expander, $skipped);
            if ([] !== $rule) {
                $rules[] = ['path' => self::_relativePath($element)] + $rule;
            }
        }

        return ['rules' => $rules, 'slices' => $slices, 'invariants' => $invariants, 'skipped' => $skipped];
    }

    private static function _relativePath(ProfileElement $element): string
    {
        return substr($element->getPath(), strpos($element->getPath(), '.') + 1);
    }

    /** @param Profile[] $chain */
    private static function _effective(Profile $profile, array $chain, string $id): null|ProfileElement
    {
        if ($profile->hasSnapshot()) {
            return $profile->getElement($id);
        }
        foreach ($chain as $p) {
            if (null !== ($e = $p->getDifferentialElement($id))) {
                return $e;
            }
        }
        return null;
    }

    /**
     * @param Profile[] $chain
     * @return ProfileConstraint[]
     */
    private static function _differentialConstraints(array $chain, string $id): array
    {
        $out = [];
        foreach ($chain as $p) {
            $e = $p->getDifferentialElement($id);
            if (null !== $e) {
                array_push($out, ...$e->getConstraints());
            }
        }
        return $out;
    }

    private static function _compileElement(ProfileElement $element, null|ValueSetExpander $expander, array &$skipped): array
    {
        $rule = [];

        if (null !== $element->getMin() && $element->getMin() > 0) {
            $rule['min'] = $element->getMin();
        }
        if (null !== $element->getMax() && PHPFHIR_UNLIMITED !== $element->getMax()) {
            $rule['max'] = $element->getMax();
        }

        if ($element->isChoice() && [] !== $element->getTypes()) {
            $types = [];
            foreach ($element->getTypes() as $type) {
                $types[$type->getCode()] = $type->getCode();
            }
            $rule['types'] = array_values($types);
        }

        if (null !== ($fixed = $element->getFixedValue())) {
            $rule[$fixed->isFixed() ? 'fixed' : 'pattern'] = $fixed->getValue();
        }

        $binding = $element->getBinding();
        if (null !== $binding && $binding->isRequired() && null !== $binding->getValueSet()) {
            $codes = $expander?->expand($binding->getValueSet());
            if (null === $codes) {
                $skipped[] = sprintf('%s: required binding to %s cannot be enumerated', $element->getId(), $binding->getValueSet());
            } else {
                $rule['codes'] = $codes;
            }
        }

        return $rule;
    }

    /**
     * @param Profile[] $chain
     * @param array<string, ProfileElement> $children Elements within the slice, keyed by path relative to it.
     */
    private static function _compileSlice(Profile $profile, array $chain, ProfileElement $slice, array $children): null|array
    {
        $slicedId = substr($slice->getId(), 0, (int)strrpos($slice->getId(), ':'));
        $discriminators = self::_slicing($profile, $chain, $slicedId)?->getDiscriminators() ?? [];
        $match = [] === $discriminators
            ? self::_matchFixedValues($slice, $children)
            : self::_matchDiscriminators($profile, $chain, $slice, $discriminators);
        if (null === $match) {
            return null;
        }

        $out = [
            'path' => self::_relativePath($slice),
            'name' => $slice->getSliceName(),
            'match' => $match,
        ];
        if (null !== $slice->getMin() && $slice->getMin() > 0) {
            $out['min'] = $slice->getMin();
        }
        if (null !== $slice->getMax() && PHPFHIR_UNLIMITED !== $slice->getMax()) {
            $out['max'] = $slice->getMax();
        }
        return $out;
    }

    /**
     * The slicing of a sliced element, which may be declared by any profile in the chain.
     *
     * @param Profile[] $chain
     */
    private static function _slicing(Profile $profile, array $chain, string $id): null|ProfileSlicing
    {
        if (null !== ($slicing = $profile->getElement($id)?->getSlicing())) {
            return $slicing;
        }
        foreach ($chain as $p) {
            if (null !== ($slicing = $p->getDifferentialElement($id)?->getSlicing())) {
                return $slicing;
            }
        }
        return null;
    }

    /**
     * Without discriminators, every fixed / pattern value within the slice is taken to identify its members.
     *
     * @param array<string, ProfileElement> $children Elements within the slice, keyed by path relative to it.
     * @return null|array<string, mixed> Null if the slice cannot be matched.
     */
    private static function _matchFixedValues(ProfileElement $slice, array $children): null|array
    {
        $match = [];

        if (null !== ($own = $slice->getFixedValue()) && is_array($own->getValue())) {
            $match = $own->getValue();
        }

        foreach ($children as $relative => $child) {
            if (null === $child->getFixedValue() || str_contains($relative, ':') || str_contains($relative, '[x]')) {
                continue;
            }
            self::_nest($match, explode('.', $relative), $child->getFixedValue()->getValue());
        }

        if (null !== ($url = self::_extensionUrl($slice))) {
            $match['url'] = $url;
        }

        return [] === $match ? null : $match;
    }

    /**
     * Matches slice members on the values at the discriminator paths only.  Only "value" and "pattern" discriminators
     * on plain element paths are supported.
     *
     * @param Profile[] $chain
     * @param array<int, array{type: string, path: string}> $discriminators
     * @return null|array<string, mixed> Null if the slice cannot be matched.
     */
    private static function _matchDiscriminators(Profile $profile, array $chain, ProfileElement $slice, array $discriminators): null|array
    {
        $match = [];
        foreach ($discriminators as $discriminator) {
            if (('value' !== $discriminator['type'] && 'pattern' !== $discriminator['type'])
                || 1 !== preg_match(self::_DISCRIMINATOR_PATH_PATTERN, $discriminator['path'])) {
                return null;
            }
            $segments = '$this' === $discriminator['path'] ? [] : explode('.', $discriminator['path']);
            if (!self::_matchDiscriminator($profile, $chain, $slice, $segments, $match)) {
                return null;
            }
        }
        return [] === $match ? null : $match;
    }

    /**
     * Adds the value the slice requires at a discriminator path to $match.  The value may be declared at the path
     * itself or, as part of a larger fixed / pattern value, on an element above it.
     *
     * @param Profile[] $chain
     * @param string[] $segments Discriminator path relative to the slice, empty for "$this".
     */
    private static function _matchDiscriminator(Profile $profile, array $chain, ProfileElement $slice, array $segments, array &$match): bool
    {
        for ($n = count($segments); $n >= 0; $n--) {
            $prefix = array_slice($segments, 0, $n);
            $element = 0 === $n ? $slice : self::_effective($profile, $chain, $slice->getId() . '.' . implode('.', $prefix));
            $value = $element?->getFixedValue()?->getValue();
            if (null === $value) {
                continue;
            }
            if ([] !== $prefix) {
                self::_nest($match, $prefix, $value);
                return true;
            }
            if (!is_array($value) || array_is_list($value)) {
                return false;
            }
            $match = array_replace_recursive($match, $value);
            return true;
        }
        if (['url'] === $segments && null !== ($url = self::_extensionUrl($slice))) {
            $match['url'] = $url;
            return true;
        }
        return false;
    }

    /**
     * Groups every element lying within a top-level slice by that slice's id.  An element's slice is the part of its
     * id up to the first "." after the first ":", so one pass covers every slice {@see compile()} handles.
     *
     * @param Profile[] $chain
     * @return array<string, array<string, ProfileElement>> slice id => path relative to the slice => element
     */
    private static function _sliceChildren(Profile $profile, array $chain): array
    {
        $out = [];
        $sources = $profile->hasSnapshot() ? [$profile->getElements()] : [];
        if ([] === $sources) {
            foreach ($chain as $p) {
                $sources[] = $p->getDifferentialElements();
            }
        }
        foreach ($sources as $elements) {
            foreach ($elements as $child) {
                $id = $child->getId();
                $colon = strpos($id, ':');
                if (false === $colon || false === ($dot = strpos($id, '.', $colon))) {
                    continue;
                }
                $out[substr($id, 0, $dot)][substr($id, $dot + 1)] ??= $child;
            }
        }
        return $out;
    }

    /** The url of the extension definition an extension slice is constrained to, if any. */
    private static function _extensionUrl(ProfileElement $slice): null|string
    {
        foreach ($slice->getTypes() as $type) {
            if ('Extension' === $type->getCode() && [] !== $type->getProfiles()) {
                return ProfilePackage::stripCanonicalVersion($type->getProfiles()[0]);
            }
        }
        return null;
    }

    private static function _nest(array &$target, array $segments, mixed $value): void
    {
        $head = array_shift($segments);
        if ([] === $segments) {
            $target[$head] = $value;
            return;
        }
        if (!isset($target[$head]) || !is_array($target[$head])) {
            $target[$head] = [];
        }
        self::_nest($target[$head], $segments, $value);
    }
}
