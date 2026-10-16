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
 * slice, slice discriminators other than fixed / pattern values and extension urls, closed slicing, and invariants,
 * which are only carried along as metadata.
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
                $slice = self::_compileSlice($profile, $chain, $element);
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
     */
    private static function _compileSlice(Profile $profile, array $chain, ProfileElement $slice): null|array
    {
        $match = [];

        if (null !== ($own = $slice->getFixedValue()) && is_array($own->getValue())) {
            $match = $own->getValue();
        }

        $prefix = $slice->getId() . '.';
        foreach ($profile->getElements() as $child) {
            if (!str_starts_with($child->getId(), $prefix) || null === $child->getFixedValue()) {
                continue;
            }
            $relative = substr($child->getId(), strlen($prefix));
            if (str_contains($relative, ':') || str_contains($relative, '[x]')) {
                continue;
            }
            self::_nest($match, explode('.', $relative), $child->getFixedValue()->getValue());
        }

        foreach ($slice->getTypes() as $type) {
            if ('Extension' === $type->getCode() && [] !== $type->getProfiles()) {
                $match['url'] = ProfilePackage::stripCanonicalVersion($type->getProfiles()[0]);
                break;
            }
        }

        if ([] === $match) {
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

    /**
     * Exports a rule map whose keys are already PHP expressions, e.g. "self::FIELD_NAME" or "'contact.relationship'".
     *
     * @param array<string, mixed> $rules
     */
    public static function exportFieldRules(array $rules, int $indent = 0): string
    {
        if ([] === $rules) {
            return '[]';
        }
        $pad = str_repeat('    ', $indent + 1);
        $out = "[\n";
        foreach ($rules as $key => $fieldRules) {
            $out .= $pad . $key . ' => ' . self::export($fieldRules, $indent + 1) . ",\n";
        }
        return $out . str_repeat('    ', $indent) . ']';
    }

    /**
     * Exports a compiled rule structure as a short-array literal, omitting keys for lists.
     */
    public static function export(mixed $value, int $indent = 0): string
    {
        if ($value instanceof ProfilePhpExpression) {
            return $value->code;
        }
        if (!is_array($value)) {
            return var_export($value, true);
        }
        if ([] === $value) {
            return '[]';
        }
        $isList = array_is_list($value);
        $pad = str_repeat('    ', $indent + 1);
        $out = "[\n";
        foreach ($value as $k => $v) {
            $out .= $pad . ($isList ? '' : var_export($k, true) . ' => ') . self::export($v, $indent + 1) . ",\n";
        }
        return $out . str_repeat('    ', $indent) . ']';
    }
}
