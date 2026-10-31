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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Enum\ValidationRuleNameEnum;
use DCarbone\PHPFHIR\Profiles\Rules\ProfileElementRule;
use DCarbone\PHPFHIR\Profiles\Rules\ProfileRules;
use DCarbone\PHPFHIR\Profiles\Rules\ProfileSliceRule;
use DCarbone\PHPFHIR\Render\PHPExpression;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Turns the compiled rules of a profile into entries for the generated class's rule map, to be run by the
 * validation system's own rules.  Elements are addressed by path, e.g. "contact.relationship"; the path to every
 * element is checked against the generated types here so the rules never point at something that does not exist.
 *
 * Anything that cannot be resolved against the types (unknown elements, unsupported value shapes) is left out and
 * reported through {@see \DCarbone\PHPFHIR\Profiles\ProfileValidationCode::$skipped}.
 */
final class ProfileValidationGenerator
{
    /** @var array<string, array<string, mixed>> */
    private array $_rules = [];
    /** @var string[] */
    private array $_constants = [];
    /** @var array<int, ProfileSliceMatcher> */
    private array $_sliceMatchers = [];
    /** @var string[] */
    private array $_skipped = [];
    private readonly ProfileTypeNavigator $_nav;
    private readonly Imports $_imports;
    private readonly string $_root;

    private function __construct(
        ProfileTypeNavigator $_nav,
        Imports $_imports,
        string $_root,
    ) {
        $this->_nav = $_nav;
        $this->_imports = $_imports;
        $this->_root = $_root;
    }

    /**
     * @param string $rootType FHIR name of the type the profile constrains.
     * @param Imports $imports Imports of the class the code is for; classes are added as needed.
     */
    public static function generate(
        ProfileRules         $rules,
        string               $rootType,
        ProfileTypeNavigator $navigator,
        Imports              $imports,
    ): ProfileValidationCode {
        $g = new self($navigator, $imports, $rootType);
        foreach ($rules->getSliceRules() as $i => $slice) {
            $g->_slice($i, $slice);
        }
        foreach ($rules->getElementRules() as $rule) {
            $g->_rule($rule);
        }
        return new ProfileValidationCode(
            implode("\n\n", $g->_constants),
            $g->_rules,
            $g->_sliceMatchers,
            $g->_skipped,
        );
    }

    /**
     * @param string[] $segments
     * @return null|string Type key reached by following $segments from the root, or null if it cannot be followed.
     */
    private function _resolve(array $segments): null|string
    {
        $typeKey = $this->_root;
        foreach ($segments as $segment) {
            $prop = $this->_nav->getProperty($typeKey, $segment);
            if (null === $prop || !$prop->isComplex()) {
                return null;
            }
            $typeKey = $prop->typeKey;
        }
        return $typeKey;
    }

    /**
     * Key of the rule map for a property: the field constant for a property of the profiled type itself, otherwise
     * the dotted path of field names, which the validation system resolves against the instance.
     *
     * @param string[] $segments Names of the parents of the property.
     */
    private function _key(array $segments, ProfileProperty $prop): string
    {
        if ([] === $segments) {
            return 'self::' . $prop->getFieldConstantName();
        }
        return $this->_pathKey($segments, $prop->field);
    }

    /** @param string[] $segments */
    private function _pathKey(array $segments, string $last): string
    {
        $segments[] = $last;
        return var_export(implode('.', $segments), true);
    }

    private function _set(string $key, ValidationRuleNameEnum $rule, mixed $constraint): void
    {
        $this->_rules[$key][$rule->value] = $constraint;
    }

    private function _rule(ProfileElementRule $rule): void
    {
        $segments = explode('.', $rule->getPath());
        $last = array_pop($segments);
        $parentType = $this->_resolve($segments);
        $base = str_ends_with($last, '[x]') ? substr($last, 0, -3) : null;
        if (null === $parentType) {
            $entries = [];
        } elseif (null !== $base) {
            $entries = $this->_nav->getChoiceProperties($parentType, $base);
        } else {
            $prop = $this->_nav->getProperty($parentType, $last);
            $entries = null === $prop ? [] : [$prop];
        }
        if ([] === $entries) {
            $this->_skipped[] = sprintf('%s: element cannot be found on the generated types', $rule->getPath());
            return;
        }

        if (null !== $base) {
            $this->_choice($rule, $segments, $last, $entries);
            return;
        }

        $prop = $entries[0];
        $key = $this->_key($segments, $prop);
        if (null !== $rule->getMin()) {
            $this->_set($key, ValidationRuleNameEnum::MIN_OCCURS, $rule->getMin());
        }
        if (null !== $rule->getMax() && ($rule->getMax() < 1 || $prop->collection)) {
            $this->_set($key, ValidationRuleNameEnum::MAX_OCCURS, $rule->getMax());
        }
        if (null !== ($fixed = $rule->getFixedValue())) {
            $problem = $this->_valueProblem($prop, $fixed->getValue());
            if (null !== $problem) {
                $this->_skipped[] = sprintf('%s: %s value cannot be checked: %s', $rule->getPath(), $fixed->getKind()->value, $problem);
            } else {
                $this->_set($key, $fixed->isFixed() ? ValidationRuleNameEnum::FIXED_VALUE : ValidationRuleNameEnum::PATTERN_VALUE, $fixed->getValue());
            }
        }
        if (null !== $rule->getCodes()) {
            $this->_set($key, ValidationRuleNameEnum::CODE_BINDING, $rule->getCodes());
        }
    }

    /**
     * @param ProfileProperty[] $variants
     */
    private function _choice(ProfileElementRule $rule, array $segments, string $last, array $variants): void
    {
        $key = $this->_pathKey($segments, $last);
        $fields = [];
        $allowed = [];
        $base = substr($last, 0, -3);
        foreach ($variants as $variant) {
            $fields[] = $variant->field;
            foreach ($rule->getTypes() as $type) {
                if ($variant->field === $base . ucfirst($type)) {
                    $allowed[] = $variant->field;
                }
            }
        }
        $constraint = ['fields' => $fields];
        if ($rule->hasTypes()) {
            if ([] === $allowed) {
                // an empty allowed list would reject every variant; the profile types are not ones this element has.
                $this->_skipped[] = sprintf(
                    '%s: none of the profile types (%s) are variants of this choice element',
                    $rule->getPath(),
                    implode(', ', $rule->getTypes()),
                );
            } elseif ($allowed !== $fields) {
                // allowing every variant restricts nothing.
                $constraint['allowed'] = $allowed;
            }
        }
        if (null !== $rule->getMin()) {
            $constraint['min'] = $rule->getMin();
        }
        if (null !== $rule->getMax()) {
            $constraint['max'] = $rule->getMax();
        }
        if (['fields'] !== array_keys($constraint)) {
            $this->_set($key, ValidationRuleNameEnum::CHOICE_OCCURS, $constraint);
        }
        if (null !== ($fixed = $rule->getFixedValue())) {
            $this->_skipped[] = sprintf('%s: %s cannot be checked on a choice element', $rule->getPath(), $fixed->getKind()->value);
        }
        if (null !== $rule->getCodes()) {
            $this->_skipped[] = sprintf('%s: codes cannot be checked on a choice element', $rule->getPath());
        }
    }

    private function _slice(int $index, ProfileSliceRule $slice): void
    {
        $segments = explode('.', $slice->getPath());
        $last = array_pop($segments);
        $parentType = str_contains($last, '[x]') ? null : $this->_resolve($segments);
        $prop = null === $parentType ? null : $this->_nav->getProperty($parentType, $last);
        if (null === $prop || !$prop->isComplex()) {
            $this->_skipped[] = sprintf('%s:%s: slice element cannot be found on the generated types', $slice->getPath(), $slice->getName());
            return;
        }
        $problem = $this->_patternProblem($prop->typeKey, $slice->getMatch());
        if (null !== $problem) {
            $this->_skipped[] = sprintf('%s:%s: slice cannot be matched against the generated types: %s', $slice->getPath(), $slice->getName(), $problem);
            return;
        }

        $constant = '_SLICE_MATCH_' . $index;
        $this->_constants[] = sprintf(
            "    /** @var array<string, mixed> */\n    private const %s = %s;",
            $constant,
            pretty_var_export($slice->getMatch(), 1),
        );
        $this->_sliceMatchers[$index] = new ProfileSliceMatcher(
            $slice,
            $constant,
            $this->_imports->addImport(...NameUtils::splitFullyQualifiedName($prop->className))->getImportedName(),
        );

        if (null === $slice->getMin() && null === $slice->getMax()) {
            return;
        }
        $entry = ['name' => $slice->getName(), 'match' => new PHPExpression('self::' . $constant)];
        if (null !== $slice->getMin()) {
            $entry['min'] = $slice->getMin();
        }
        if (null !== $slice->getMax()) {
            $entry['max'] = $slice->getMax();
        }
        $key = $this->_key($segments, $prop);
        $this->_rules[$key][ValidationRuleNameEnum::SLICE_OCCURS->value][] = $entry;
    }

    /** @return null|string Why the value cannot be checked against the property, or null if it can. */
    private function _valueProblem(ProfileProperty $prop, mixed $expected): null|string
    {
        if (!$prop->isComplex()) {
            return is_array($expected) ? 'complex value for a primitive element' : null;
        }
        return is_array($expected) ? $this->_patternProblem($prop->typeKey, $expected) : 'primitive value for a complex element';
    }

    /** @return null|string Why the pattern cannot be checked against the type, or null if it can. */
    private function _patternProblem(string $typeKey, array $pattern): null|string
    {
        $entries = array_is_list($pattern) ? $pattern : [$pattern];
        foreach ($entries as $entry) {
            if (!is_array($entry) || [] === $entry || array_is_list($entry)) {
                return 'unsupported pattern shape';
            }
            foreach ($entry as $field => $expected) {
                $prop = $this->_nav->getProperty($typeKey, (string)$field);
                if (null === $prop) {
                    return sprintf('%s: element cannot be found on the generated types', $field);
                }
                $problem = $this->_valueProblem($prop, $expected);
                if (null !== $problem) {
                    return sprintf('%s: %s', $field, $problem);
                }
            }
        }
        return null;
    }
}
