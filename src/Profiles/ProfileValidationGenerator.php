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

use DCarbone\PHPFHIR\Enum\ProfilePropertyKindEnum;
use DCarbone\PHPFHIR\Builder\Imports;

/**
 * Turns the compiled rules of a profile into straight-line PHP that checks hydrated generated types through their
 * own getters.  Nothing is serialized or looked up at runtime; the path to every element is resolved here.
 *
 * Anything that cannot be resolved against the types (unknown elements, unsupported value shapes) is left out and
 * reported through {@see \DCarbone\PHPFHIR\Profiles\ProfileValidationCode::$skipped}.
 */
final class ProfileValidationGenerator
{
    private const _PAD = '    ';

    /** @var string[] */
    private array $_out = [];
    private int $_indent = 2;
    private int $_depth = 0;

    /** @var array<string, string> matcher key => method name */
    private array $_matchers = [];
    /** @var array<string, string> method name => source */
    private array $_methods = [];
    /** @var array<string, string> constant name => source */
    private array $_constants = [];
    /** @var array<int, array{method: string, class: string}> */
    private array $_sliceMatchers = [];
    /** @var string[] */
    private array $_skipped = [];
    private bool $_needsCodeAllowed = false;
    /** @var array<string, array<string, int>> */
    private array $_fieldRules = [];
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
        if ($g->_needsCodeAllowed) {
            $g->_methods['_codeAllowed'] = self::_codeAllowedSource();
        }
        return new ProfileValidationCode(
            implode("\n", $g->_out),
            implode("\n\n", $g->_constants),
            implode("\n\n", $g->_methods),
            $g->_sliceMatchers,
            $g->_skipped,
            $g->_fieldRules,
        );
    }

    private function _emit(string $line): void
    {
        $this->_out[] = '' === $line ? '' : str_repeat(self::_PAD, $this->_indent) . $line;
    }

    /** @return string[] */
    private function _capture(callable $fn): array
    {
        $saved = $this->_out;
        $this->_out = [];
        $fn();
        $lines = $this->_out;
        $this->_out = $saved;
        return $lines;
    }

    /** Marks characters that need escaping once it is known how the path is quoted; see _quote(). */
    private static function _esc(string $text): string
    {
        return strtr($text, ['\\' => "\x01", '"' => "\x02", '$' => "\x03", "'" => "\x04"]);
    }

    /** Quotes a path, interpolating only when it has variables in it. */
    private static function _quote(string $path): string
    {
        if (str_contains($path, '{$')) {
            return '"' . strtr($path, ["\x01" => '\\\\', "\x02" => '\\"', "\x03" => '\\$', "\x04" => "'"]) . '"';
        }
        return "'" . strtr($path, ["\x01" => '\\\\', "\x02" => '"', "\x03" => '$', "\x04" => "\\'"]) . "'";
    }

    private function _cat(string $path, string $part): string
    {
        return $path . $part;
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
     * Emits the loops / null checks leading to every element the segments reach, then whatever $inner emits.
     *
     * @param string[] $segments
     */
    private function _walk(array $segments, string $var, string $typeKey, string $path, callable $inner): void
    {
        if ([] === $segments) {
            $inner($var, $typeKey, $path);
            return;
        }
        $segment = array_shift($segments);
        $prop = $this->_nav->getProperty($typeKey, $segment);
        // one set of names per nesting level
        $n = $this->_depth + 1;
        $i = '$i' . (1 === $n ? '' : $n);
        $v = '$v' . (1 === $n ? '' : $n);
        if ($prop->collection) {
            $this->_emit(sprintf('foreach (%s->%s() as %s => %s) {', $var, $prop->getter, $i, $v));
            $next = $this->_cat($path, sprintf('%s[{%s}].', $segment, $i));
        } else {
            $this->_emit(sprintf('%s = %s->%s();', $v, $var, $prop->getter));
            $this->_emit(sprintf('if (null !== %s) {', $v));
            $next = $this->_cat($path, $segment . '.');
        }
        $this->_indent++;
        $this->_depth++;
        $this->_walk($segments, $v, $prop->typeKey, $next, $inner);
        $this->_depth--;
        $this->_indent--;
        $this->_emit('}');
    }

    /** @return ProfileProperty[] */
    private function _entries(string $parentType, string $last): array
    {
        if (str_ends_with($last, '[x]')) {
            return $this->_nav->getChoiceProperties($parentType, substr($last, 0, -3));
        }
        $prop = $this->_nav->getProperty($parentType, $last);
        return null === $prop ? [] : [$prop];
    }

    private function _violation(string $path, string $rule, string $message): string
    {
        return sprintf('$errs[%s][%s] = %s;', self::_quote($path), var_export('profile_' . strtolower(substr($rule, 5)), true), $message);
    }

    private function _rule(array $rule): void
    {
        $segments = explode('.', $rule['path']);
        $last = array_pop($segments);
        $parentType = $this->_resolve($segments);
        $entries = null === $parentType ? [] : $this->_entries($parentType, $last);
        if ([] === $entries) {
            $this->_skipped[] = sprintf('%s: element cannot be found on the generated types', $rule['path']);
            return;
        }

        $base = str_ends_with($last, '[x]') ? substr($last, 0, -3) : null;
        if ([] === $segments && null === $base && 1 === count($entries)) {
            $rule = $this->_delegateOccurs($entries[0], $rule);
            if ([] === array_diff(array_keys($rule), ['path'])) {
                return;
            }
        }
        $this->_walk($segments, '$this', $this->_root, '', function (string $var, string $typeKey, string $path) use ($rule, $last, $entries, $base): void {
            $count = '$c';
            $lines = $this->_capture(function () use ($entries, $var, $path, $count, $rule, $base): void {
                foreach ($entries as $prop) {
                    $this->_entry($prop, $var, $path, $count, $rule, $base);
                }
            });
            // a lone collection that is only counted needs no zeroing first
            $prefix = str_repeat(self::_PAD, $this->_indent) . $count . ' += ';
            if (1 === count($lines) && str_starts_with($lines[0], $prefix)) {
                $this->_emit($count . ' = ' . substr($lines[0], strlen($prefix)));
            } else {
                $this->_emit($count . ' = 0;');
                array_push($this->_out, ...$lines);
            }
            $display = self::_esc($last);
            if (isset($rule['min'])) {
                $this->_emit(sprintf('if (%s < %d) {', $count, $rule['min']));
                $this->_emit(self::_PAD . $this->_violation(
                    $this->_cat($path, $display),
                    'RULE_MIN',
                    sprintf("sprintf('requires at least %d, found %%d', %s)", $rule['min'], $count),
                ));
                $this->_emit('}');
            }
            if (isset($rule['max']) && ($rule['max'] < 1 || 1 < count($entries) || $entries[0]->collection)) {
                $message = 0 === $rule['max']
                    ? "'is prohibited'"
                    : sprintf("sprintf('allows at most %d, found %%d', %s)", $rule['max'], $count);
                $this->_emit(sprintf('if (%s > %d) {', $count, $rule['max']));
                $this->_emit(self::_PAD . $this->_violation($this->_cat($path, $display), 'RULE_MAX', $message));
                $this->_emit('}');
            }
        });
    }

    /**
     * The maximum of a top-level collection is enforced by the type's own max_occurs rule; this hands it over and
     * returns whatever is left for generated code.  Minimums stay generated because a profile counts
     * them across slices and nested paths, and has its own minimum that may differ from the type's.
     */
    private function _delegateOccurs(ProfileProperty $prop, array $rule): array
    {
        if (isset($rule['max']) && $rule['max'] > 0 && $prop->collection) {
            $this->_fieldRules[$prop->field]['max_occurs'] = $rule['max'];
            unset($rule['max']);
        }
        return $rule;
    }

    private function _entry(ProfileProperty $prop, string $var, string $path, string $count, array $rule, null|string $base): void
    {
        $element = '$e';

        $single = !$prop->collection;
        $valuePath = $single
            ? $this->_cat($path, $prop->field)
            : $this->_cat($path, sprintf('%s[{$j}]', $prop->field));

        $this->_indent++;
        $checks = $this->_capture(fn() => $this->_checks($prop, $element, $valuePath, $rule, $base));
        $this->_indent--;

        if ([] === $checks) {
            $this->_emit($single
                ? sprintf('if (null !== %s->%s()) {', $var, $prop->getter)
                : sprintf('%s += count(%s->%s());', $count, $var, $prop->getter));
            if ($single) {
                $this->_emit(self::_PAD . $count . '++;');
                $this->_emit('}');
            }
            return;
        }

        if ($single) {
            $this->_emit(sprintf('%s = %s->%s();', $element, $var, $prop->getter));
            $this->_emit(sprintf('if (null !== %s) {', $element));
        } else {
            $this->_emit(sprintf('foreach (%s->%s() as $j => %s) {', $var, $prop->getter, $element));
        }
        $this->_emit(self::_PAD . $count . '++;');
        array_push($this->_out, ...$checks);
        $this->_emit('}');
    }

    private function _checks(ProfileProperty $prop, string $element, string $path, array $rule, null|string $base): void
    {
        if (null !== $base && isset($rule['types'])) {
            $suffix = substr($prop->field, strlen($base));
            $allowed = [];
            foreach ($rule['types'] as $type) {
                $allowed[] = ucfirst($type);
            }
            if (!in_array($suffix, $allowed, true)) {
                $this->_emit($this->_violation(
                    $path,
                    'RULE_TYPE',
                    var_export(sprintf('type %s is not allowed, expected one of: %s', $suffix, implode(', ', $rule['types'])), true),
                ));
            }
        }

        foreach (['fixed' => ['RULE_FIXED', 'does not match the fixed value'], 'pattern' => ['RULE_PATTERN', 'does not match the required pattern']] as $kind => [$const, $message]) {
            if (!array_key_exists($kind, $rule)) {
                continue;
            }
            $mismatch = $this->_mismatch($prop, $element, $rule[$kind]);
            if (null === $mismatch) {
                $this->_skipped[] = sprintf('%s: %s value cannot be checked against %s', $rule['path'], $kind, $prop->className);
                continue;
            }
            $this->_emit(sprintf('if (%s) {', $mismatch));
            $this->_emit(self::_PAD . $this->_violation($path, $const, var_export($message, true)));
            $this->_emit('}');
        }

        if (isset($rule['codes'])) {
            $bound = $this->_bound($prop, $rule['codes']);
            if (null === $bound) {
                $this->_skipped[] = sprintf('%s: required binding cannot be checked against %s', $rule['path'], $prop->className);
            } else {
                $this->_emit(sprintf('if (!self::%s(%s)) {', $bound, $element));
                $this->_emit(self::_PAD . $this->_violation($path, 'RULE_BINDING', "'does not contain a code from the required value set'"));
                $this->_emit('}');
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
        $method = $this->_matcher($prop->typeKey, $prop->className, $slice['match']);
        if (null === $method) {
            $this->_skipped[] = sprintf('%s:%s: slice cannot be matched against the generated types', $slice['path'], $slice['name']);
            return;
        }
        $this->_sliceMatchers[$index] = ['method' => $method, 'class' => $this->_import($prop->className)];

        if (!isset($slice['min']) && !isset($slice['max'])) {
            return;
        }

        $this->_walk($segments, '$this', $this->_root, '', function (string $var, string $typeKey, string $path) use ($slice, $last, $prop, $method): void {
            $count = '$c';
            $this->_emit($count . ' = 0;');
            if ($prop->collection) {
                $this->_emit(sprintf('foreach (%s->%s() as $e) {', $var, $prop->getter));
                $this->_emit(self::_PAD . sprintf('if (self::%s($e)) {', $method));
                $this->_emit(self::_PAD . self::_PAD . $count . '++;');
                $this->_emit(self::_PAD . '}');
            } else {
                $this->_emit(sprintf('$e = %s->%s();', $var, $prop->getter));
                $this->_emit(sprintf('if (null !== $e && self::%s($e)) {', $method));
                $this->_emit(self::_PAD . $count . '++;');
            }
            $this->_emit('}');

            $sliceName = self::_esc($last . ':' . $slice['name']);
            foreach (['min' => ['<', 'RULE_SLICE_MIN', 'requires'], 'max' => ['>', 'RULE_SLICE_MAX', 'allows']] as $bound => [$op, $const, $verb]) {
                if (!isset($slice[$bound])) {
                    continue;
                }
                $this->_emit(sprintf('if (%s %s %d) {', $count, $op, $slice[$bound]));
                $this->_emit(self::_PAD . $this->_violation(
                    $this->_cat($path, $sliceName),
                    $const,
                    sprintf("sprintf('slice %s at %s %d, found %%d', %s)", $verb, 'min' === $bound ? 'least' : 'most', $slice[$bound], $count),
                ));
                $this->_emit('}');
            }
        });
    }

    private function _import(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');
        return $this->_imports->addImport(substr($fqcn, 0, $pos), substr($fqcn, $pos + 1))->getImportedName();
    }

    /** PHP expression for the raw scalar held by a primitive-ish object expression. */
    private static function _raw(ProfileProperty $prop, string $object, string $arrow = '->'): string
    {
        return ProfilePropertyKindEnum::CONTAINER === $prop->kind
            ? $object . $arrow . 'getValue()?->getValue()'
            : $object . $arrow . 'getValue()';
    }

    /** PHP expression that is true when a scalar does not equal the expected one. */
    private function _scalarMismatch(string $raw, mixed $expected): null|string
    {
        if (is_string($expected)) {
            return $raw . ' !== ' . var_export($expected, true);
        }
        if (is_bool($expected)) {
            return $raw . ' !== ' . var_export($expected, true);
        }
        if (is_int($expected) || is_float($expected)) {
            return sprintf('null === ($r = %s) || (float)$r != %s', $raw, var_export((float)$expected, true));
        }
        return null;
    }

    /**
     * @return null|string PHP expression that is true when the element does not satisfy the fixed / pattern value.
     */
    private function _mismatch(ProfileProperty $prop, string $element, mixed $expected): null|string
    {
        if (!$prop->isComplex()) {
            return is_array($expected) ? 'true' : $this->_scalarMismatch(self::_raw($prop, $element), $expected);
        }
        if (!is_array($expected) || [] === $expected || array_is_list($expected)) {
            return is_array($expected) ? null : 'true';
        }
        $method = $this->_matcher($prop->typeKey, $prop->className, $expected);
        return null === $method ? null : sprintf('!self::%s(%s)', $method, $element);
    }

    /**
     * Registers a private static method telling whether an object of the type holds everything in the pattern.
     *
     * @return null|string Method name, or null if nothing in the pattern could be checked.
     */
    private function _matcher(string $typeKey, string $className, array $pattern): null|string
    {
        $id = $typeKey . '|' . serialize($pattern);
        if (array_key_exists($id, $this->_matchers)) {
            return $this->_matchers[$id];
        }

        $lines = [];
        foreach ($pattern as $field => $expected) {
            $prop = $this->_nav->getProperty($typeKey, (string)$field);
            if (null === $prop) {
                $this->_skipped[] = sprintf('%s.%s: element cannot be found on the generated types', $typeKey, $field);
                continue;
            }
            $before = count($lines);
            $this->_matchField($lines, $prop, $expected);
            if (count($lines) === $before) {
                $this->_skipped[] = sprintf('%s.%s: value cannot be checked', $typeKey, $field);
            }
        }

        if ([] === $lines) {
            return $this->_matchers[$id] = null;
        }

        $name = '_match' . (count($this->_matchers) + 1);
        $this->_matchers[$id] = $name;
        $lines[] = self::_PAD . self::_PAD . 'return true;';
        $this->_methods[$name] = sprintf(
            "    private static function %s(%s \$item): bool\n    {\n%s\n    }",
            $name,
            $this->_import($className),
            implode("\n", $lines),
        );
        return $name;
    }

    /** @param string[] $lines */
    private function _matchField(array &$lines, ProfileProperty $prop, mixed $expected): void
    {
        $pad = self::_PAD . self::_PAD;

        if (!$prop->isComplex()) {
            if (is_array($expected)) {
                $lines[] = $pad . 'return false;';
                return;
            }
            $mismatch = $this->_scalarMismatch(self::_raw($prop, sprintf('$item->%s()', $prop->getter), '?->'), $expected);
            if (null === $mismatch) {
                return;
            }
            $lines[] = sprintf('%sif (%s) {', $pad, $mismatch);
            $lines[] = $pad . self::_PAD . 'return false;';
            $lines[] = $pad . '}';
            return;
        }

        if (!is_array($expected)) {
            $lines[] = $pad . 'return false;';
            return;
        }

        $wanted = array_is_list($expected) ? $expected : [$expected];
        foreach ($wanted as $one) {
            if (!is_array($one) || [] === $one || array_is_list($one)) {
                continue;
            }
            $method = $this->_matcher($prop->typeKey, $prop->className, $one);
            if (null === $method) {
                continue;
            }
            if ($prop->collection) {
                $lines[] = $pad . '$found = false;';
                $lines[] = sprintf('%sforeach ($item->%s() as $c) {', $pad, $prop->getter);
                $lines[] = sprintf('%s    if (self::%s($c)) {', $pad, $method);
                $lines[] = $pad . '        $found = true;';
                $lines[] = $pad . '        break;';
                $lines[] = $pad . '    }';
                $lines[] = $pad . '}';
                $lines[] = $pad . 'if (!$found) {';
                $lines[] = $pad . '    return false;';
                $lines[] = $pad . '}';
            } else {
                $lines[] = sprintf('%s$c = $item->%s();', $pad, $prop->getter);
                $lines[] = sprintf('%sif (null === $c || !self::%s($c)) {', $pad, $method);
                $lines[] = $pad . '    return false;';
                $lines[] = $pad . '}';
            }
        }
    }

    /**
     * Registers a private static method telling whether an element holds one of the allowed codes.
     *
     * @param array<int, array{0: null|string, 1: string}> $codes
     * @return null|string Method name, or null if the element's type cannot be checked.
     */
    private function _bound(ProfileProperty $prop, array $codes): null|string
    {
        $id = 'bound|' . $prop->typeKey . '|' . serialize($codes);
        if (array_key_exists($id, $this->_matchers)) {
            return $this->_matchers[$id];
        }

        $n = count($this->_matchers) + 1;
        $const = 'self::_CODES_' . $n;

        $body = null;
        if (!$prop->isComplex()) {
            $body = sprintf('return self::_codeAllowed(%s, null, %s);', $const, self::_raw($prop, '$item'));
        } else {
            $coding = $this->_codingCheck($prop->typeKey, $const, '$item');
            if (null !== $coding) {
                $body = 'return ' . $coding . ';';
            } else {
                $codingProp = $this->_nav->getProperty($prop->typeKey, 'coding');
                $inner = null !== $codingProp && $codingProp->collection && $codingProp->isComplex()
                    ? $this->_codingCheck($codingProp->typeKey, $const, '$coding')
                    : null;
                if (null !== $inner) {
                    $body = sprintf(
                        "foreach (\$item->%s() as \$coding) {\n            if (%s) {\n                return true;\n            }\n        }\n        return false;",
                        $codingProp->getter,
                        $inner,
                    );
                }
            }
        }
        if (null === $body) {
            return $this->_matchers[$id] = null;
        }

        $this->_needsCodeAllowed = true;
        $name = '_bound' . $n;
        $this->_matchers[$id] = $name;
        $this->_constants[$const] = sprintf(
            "    /** @var array<int, array{0: null|string, 1: string}> */\n    private const %s = %s;",
            substr($const, 6),
            ProfileRules::export($codes, 1),
        );
        $this->_methods[$name] = sprintf(
            "    private static function %s(%s \$item): bool\n    {\n        %s\n    }",
            $name,
            $this->_import($prop->className),
            $body,
        );
        return $name;
    }

    /** Expression checking a Coding-like object's system / code against the allowed codes, or null if it isn't one. */
    private function _codingCheck(string $typeKey, string $const, string $object): null|string
    {
        $system = $this->_nav->getProperty($typeKey, 'system');
        $code = $this->_nav->getProperty($typeKey, 'code');
        if (null === $code || $code->isComplex() || $code->collection || (null !== $system && ($system->isComplex() || $system->collection))) {
            return null;
        }
        return sprintf(
            'self::_codeAllowed(%s, %s, %s)',
            $const,
            null === $system ? 'null' : self::_raw($system, sprintf('%s->%s()', $object, $system->getter), '?->'),
            self::_raw($code, sprintf('%s->%s()', $object, $code->getter), '?->'),
        );
    }

    private static function _codeAllowedSource(): string
    {
        return <<<'PHP'
    /**
     * @param array<int, array{0: null|string, 1: string}> $codes [system, code] pairs.
     */
    private static function _codeAllowed(array $codes, null|string $system, null|string $code): bool
    {
        if (null === $code) {
            return false;
        }
        foreach ($codes as [$allowedSystem, $allowedCode]) {
            if ($code === $allowedCode && (null === $system || null === $allowedSystem || $system === $allowedSystem)) {
                return true;
            }
        }
        return false;
    }
PHP;
    }
}
