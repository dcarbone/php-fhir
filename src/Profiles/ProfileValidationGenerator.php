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
    /** @var array<int, array{constant: string, class: string}> */
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
     * @param array{rules: array<int, array<string, mixed>>, slices: array<int, array<string, mixed>>} $compiled
     * @param string $rootType FHIR name of the type the profile constrains.
     * @param Imports $imports Imports of the class the code is for; classes are added as needed.
     */
    public static function generate(
        array                $compiled,
        string               $rootType,
        ProfileTypeNavigator $navigator,
        Imports              $imports,
    ): ProfileValidationCode {
        $g = new self($navigator, $imports, $rootType);
        foreach ($compiled['slices'] as $i => $slice) {
            $g->_slice($i, $slice);
        }
        foreach ($compiled['rules'] as $rule) {
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

    private function _rule(array $rule): void
    {
        $segments = explode('.', $rule['path']);
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
            $this->_skipped[] = sprintf('%s: element cannot be found on the generated types', $rule['path']);
            return;
        }

        if (null !== $base) {
            $this->_choice($rule, $segments, $last, $entries);
            return;
        }

        $prop = $entries[0];
        $key = $this->_key($segments, $prop);
        if (isset($rule['min'])) {
            $this->_set($key, ValidationRuleNameEnum::MIN_OCCURS, $rule['min']);
        }
        if (isset($rule['max']) && ($rule['max'] < 1 || $prop->collection)) {
            $this->_set($key, ValidationRuleNameEnum::MAX_OCCURS, $rule['max']);
        }
        foreach (['fixed' => ValidationRuleNameEnum::FIXED_VALUE, 'pattern' => ValidationRuleNameEnum::PATTERN_VALUE] as $kind => $name) {
            if (!array_key_exists($kind, $rule)) {
                continue;
            }
            $problem = $this->_valueProblem($prop, $rule[$kind]);
            if (null !== $problem) {
                $this->_skipped[] = sprintf('%s: %s value cannot be checked: %s', $rule['path'], $kind, $problem);
                continue;
            }
            $this->_set($key, $name, $rule[$kind]);
        }
        if (isset($rule['codes'])) {
            $this->_set($key, ValidationRuleNameEnum::CODE_BINDING, $rule['codes']);
        }
    }

    /**
     * @param ProfileProperty[] $variants
     */
    private function _choice(array $rule, array $segments, string $last, array $variants): void
    {
        $key = $this->_pathKey($segments, $last);
        $fields = [];
        $allowed = [];
        $base = substr($last, 0, -3);
        foreach ($variants as $variant) {
            $fields[] = $variant->field;
            foreach ($rule['types'] ?? [] as $type) {
                if ($variant->field === $base . ucfirst($type)) {
                    $allowed[] = $variant->field;
                }
            }
        }
        $constraint = ['fields' => $fields];
        // allowing every variant restricts nothing.
        if (isset($rule['types']) && $allowed !== $fields) {
            $constraint['allowed'] = $allowed;
        }
        foreach (['min', 'max'] as $bound) {
            if (isset($rule[$bound])) {
                $constraint[$bound] = $rule[$bound];
            }
        }
        if (['fields'] !== array_keys($constraint)) {
            $this->_set($key, ValidationRuleNameEnum::CHOICE_OCCURS, $constraint);
        }
        foreach (['fixed', 'pattern', 'codes'] as $unsupported) {
            if (array_key_exists($unsupported, $rule)) {
                $this->_skipped[] = sprintf('%s: %s cannot be checked on a choice element', $rule['path'], $unsupported);
            }
        }
    }

    private function _slice(int $index, array $slice): void
    {
        $segments = explode('.', $slice['path']);
        $last = array_pop($segments);
        $parentType = str_contains($last, '[x]') ? null : $this->_resolve($segments);
        $prop = null === $parentType ? null : $this->_nav->getProperty($parentType, $last);
        if (null === $prop || !$prop->isComplex()) {
            $this->_skipped[] = sprintf('%s:%s: slice element cannot be found on the generated types', $slice['path'], $slice['name']);
            return;
        }
        $problem = $this->_patternProblem($prop->typeKey, $slice['match']);
        if (null !== $problem) {
            $this->_skipped[] = sprintf('%s:%s: slice cannot be matched against the generated types: %s', $slice['path'], $slice['name'], $problem);
            return;
        }

        $constant = '_SLICE_MATCH_' . $index;
        $this->_constants[] = sprintf(
            "    /** @var array<string, mixed> */\n    private const %s = %s;",
            $constant,
            pretty_var_export($slice['match'], 1),
        );
        $this->_sliceMatchers[$index] = ['constant' => $constant, 'class' => $this->_imports->addImport(...NameUtils::splitFullyQualifiedName($prop->className))->getImportedName()];

        if (!isset($slice['min']) && !isset($slice['max'])) {
            return;
        }
        $entry = ['name' => $slice['name'], 'match' => new PHPExpression('self::' . $constant)];
        foreach (['min', 'max'] as $bound) {
            if (isset($slice[$bound])) {
                $entry[$bound] = $slice[$bound];
            }
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
