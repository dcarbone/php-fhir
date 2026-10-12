<?php declare(strict_types=1);

/*
 * Copyright 2018-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var string $copyright */
/** @var string $namespace */
/** @var string $className */
/** @var string $violationClassName */

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $copyright; ?>


/**
 * Checks resources against the rules compiled into generated profile classes.
 *
 * The resource is examined in its FHIR JSON form.  FHIRPath invariants are not evaluated.
 */
abstract class <?php echo $className; ?>

{
    /**
     * @param mixed $resource Anything that can be json encoded to a FHIR resource or element.
     * @param array<int, array<string, mixed>> $rules
     * @param array<int, array<string, mixed>> $slices
     * @return \<?php echo $namespace; ?>\<?php echo $violationClassName; ?>[]
     */
    public static function validate(mixed $resource, array $rules, array $slices): array
    {
        $data = json_decode(json_encode($resource, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            return [];
        }

        $violations = [];
        foreach ($rules as $rule) {
            self::_applyRule($data, $rule, $violations);
        }
        foreach ($slices as $slice) {
            self::_applySlice($data, $slice, $violations);
        }
        return $violations;
    }

    private static function _applyRule(array $root, array $rule, array &$violations): void
    {
        $segments = explode('.', $rule['path']);
        $last = array_pop($segments);

        foreach (self::_parents($root, $segments) as [$parent, $parentPath]) {
            $entries = self::_entries($parent, $last);
            $count = 0;
            foreach ($entries as [$key, $values, $isList]) {
                $count += count($values);
            }
            $path = $parentPath . $last;

            if (isset($rule['min']) && $count < $rule['min']) {
                $violations[] = new <?php echo $violationClassName; ?>($path, <?php echo $violationClassName; ?>::RULE_MIN, sprintf('requires at least %d, found %d', $rule['min'], $count));
            }
            if (isset($rule['max']) && $count > $rule['max']) {
                $violations[] = new <?php echo $violationClassName; ?>($path, <?php echo $violationClassName; ?>::RULE_MAX, 0 === $rule['max']
                    ? 'is prohibited'
                    : sprintf('allows at most %d, found %d', $rule['max'], $count));
            }

            foreach ($entries as [$key, $values, $isList]) {
                if (isset($rule['types']) && str_ends_with($last, '[x]')) {
                    $suffix = substr($key, strlen($last) - 3);
                    $allowed = array_map('ucfirst', $rule['types']);
                    if (!in_array($suffix, $allowed, true)) {
                        $violations[] = new <?php echo $violationClassName; ?>($parentPath . $key, <?php echo $violationClassName; ?>::RULE_TYPE, sprintf(
                            'type %s is not allowed, expected one of: %s',
                            $suffix,
                            implode(', ', $rule['types']),
                        ));
                    }
                }
                foreach ($values as $i => $value) {
                    $valuePath = $parentPath . $key . ($isList ? '[' . $i . ']' : '');
                    if (array_key_exists('fixed', $rule) && !self::_equals($rule['fixed'], $value)) {
                        $violations[] = new <?php echo $violationClassName; ?>($valuePath, <?php echo $violationClassName; ?>::RULE_FIXED, 'does not match the fixed value');
                    }
                    if (array_key_exists('pattern', $rule) && !self::_matches($rule['pattern'], $value)) {
                        $violations[] = new <?php echo $violationClassName; ?>($valuePath, <?php echo $violationClassName; ?>::RULE_PATTERN, 'does not match the required pattern');
                    }
                    if (isset($rule['codes']) && !self::_inCodes($rule['codes'], $value)) {
                        $violations[] = new <?php echo $violationClassName; ?>($valuePath, <?php echo $violationClassName; ?>::RULE_BINDING, 'does not contain a code from the required value set');
                    }
                }
            }
        }
    }

    private static function _applySlice(array $root, array $slice, array &$violations): void
    {
        $segments = explode('.', $slice['path']);
        $last = array_pop($segments);

        foreach (self::_parents($root, $segments) as [$parent, $parentPath]) {
            $count = 0;
            foreach (self::_entries($parent, $last) as [$key, $values, $isList]) {
                foreach ($values as $value) {
                    if (self::_matches($slice['match'], $value)) {
                        $count++;
                    }
                }
            }
            $path = $parentPath . $last . ':' . $slice['name'];
            if (isset($slice['min']) && $count < $slice['min']) {
                $violations[] = new <?php echo $violationClassName; ?>($path, <?php echo $violationClassName; ?>::RULE_SLICE_MIN, sprintf('slice requires at least %d, found %d', $slice['min'], $count));
            }
            if (isset($slice['max']) && $count > $slice['max']) {
                $violations[] = new <?php echo $violationClassName; ?>($path, <?php echo $violationClassName; ?>::RULE_SLICE_MAX, sprintf('slice allows at most %d, found %d', $slice['max'], $count));
            }
        }
    }

    /**
     * Whether $item is one of the items described by a slice's match pattern.
     */
    public static function matchesSlice(mixed $item, array $match): bool
    {
        $data = json_decode(json_encode($item, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        return self::_matches($match, $data);
    }

    /**
     * Every object found by following $segments from $root, with the path leading to it.
     *
     * @return array<int, array{0: array, 1: string}>
     */
    private static function _parents(array $root, array $segments): array
    {
        $current = [[$root, '']];
        foreach ($segments as $segment) {
            $next = [];
            foreach ($current as [$obj, $path]) {
                foreach (self::_entries($obj, $segment) as [$key, $values, $isList]) {
                    foreach ($values as $i => $value) {
                        if (is_array($value)) {
                            $next[] = [$value, $path . $key . ($isList ? '[' . $i . ']' : '') . '.'];
                        }
                    }
                }
            }
            $current = $next;
        }
        return $current;
    }

    /**
     * The values of one element of $obj as a list of [json key, values]: a single entry for a normal element, one
     * entry per present type for a choice element.  Primitive extensions ("_name") count as the element being present.
     *
     * @return array<int, array{0: string, 1: array, 2: bool}>
     */
    private static function _entries(array $obj, string $segment): array
    {
        $keys = [];
        if (str_ends_with($segment, '[x]')) {
            $base = substr($segment, 0, -3);
            foreach (array_keys($obj) as $k) {
                $k = (string)$k;
                $name = str_starts_with($k, '_') ? substr($k, 1) : $k;
                if (1 === preg_match('/^' . preg_quote($base, '/') . '[A-Z]/', $name)) {
                    $keys[$name] = true;
                }
            }
            $keys = array_keys($keys);
        } else {
            $keys = [$segment];
        }

        $out = [];
        foreach ($keys as $key) {
            $value = $obj[$key] ?? null;
            $ext = $obj['_' . $key] ?? null;
            if (null === $value && null === $ext) {
                continue;
            }
            $source = $value ?? $ext;
            $isList = is_array($source) && array_is_list($source);
            $out[] = [(string)$key, $isList ? $source : [$source], $isList];
        }
        return $out;
    }

    private static function _equals(mixed $expected, mixed $actual): bool
    {
        if (is_array($expected) && is_array($actual)) {
            if (count($expected) !== count($actual)) {
                return false;
            }
            if (array_is_list($expected) && array_is_list($actual)) {
                foreach ($expected as $i => $v) {
                    if (!self::_equals($v, $actual[$i])) {
                        return false;
                    }
                }
                return true;
            }
            foreach ($expected as $k => $v) {
                if (!array_key_exists($k, $actual) || !self::_equals($v, $actual[$k])) {
                    return false;
                }
            }
            return true;
        }
        if ((is_int($expected) || is_float($expected)) && (is_int($actual) || is_float($actual))) {
            return $expected == $actual;
        }
        return $expected === $actual;
    }

    /** Everything in $pattern must be present in $actual, which may contain more. */
    private static function _matches(mixed $pattern, mixed $actual): bool
    {
        if (is_array($pattern)) {
            if (array_is_list($pattern)) {
                $candidates = is_array($actual) && array_is_list($actual) ? $actual : [$actual];
                foreach ($pattern as $p) {
                    $found = false;
                    foreach ($candidates as $c) {
                        if (self::_matches($p, $c)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        return false;
                    }
                }
                return true;
            }
            if (is_array($actual) && array_is_list($actual)) {
                foreach ($actual as $c) {
                    if (self::_matches($pattern, $c)) {
                        return true;
                    }
                }
                return false;
            }
            if (!is_array($actual)) {
                return false;
            }
            foreach ($pattern as $k => $v) {
                if (!array_key_exists($k, $actual) || !self::_matches($v, $actual[$k])) {
                    return false;
                }
            }
            return true;
        }
        return self::_equals($pattern, $actual);
    }

    /**
     * @param array<int, array{0: null|string, 1: string}> $codes [system, code] pairs.
     */
    private static function _inCodes(array $codes, mixed $value): bool
    {
        $candidates = [];
        if (is_string($value)) {
            $candidates[] = [null, $value];
        } elseif (is_array($value)) {
            if (isset($value['coding']) && is_array($value['coding'])) {
                foreach ($value['coding'] as $coding) {
                    if (is_array($coding) && isset($coding['code'])) {
                        $candidates[] = [$coding['system'] ?? null, (string)$coding['code']];
                    }
                }
            } elseif (isset($value['code'])) {
                $candidates[] = [$value['system'] ?? null, (string)$value['code']];
            }
        }

        foreach ($candidates as [$system, $code]) {
            foreach ($codes as [$allowedSystem, $allowedCode]) {
                if ($code === $allowedCode && (null === $system || null === $allowedSystem || $system === $allowedSystem)) {
                    return true;
                }
            }
        }
        return false;
    }
}
<?php return ob_get_clean();
