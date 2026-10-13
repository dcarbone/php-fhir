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
 * An ElementDefinition within a profile.  Any attribute a profile does not state is null (or empty), meaning the
 * element inherits it from its base.  Snapshot elements state everything.
 */
class ProfileElement
{
    private string $_id;
    private string $_path;
    private null|string $_sliceName;
    private null|int $_min;
    private null|int $_max;
    /** @var \DCarbone\PHPFHIR\Profiles\ProfileElementType[] */
    private array $_types = [];
    private null|ProfileFixedValue $_fixedValue;
    private null|ProfileBinding $_binding;
    private null|ProfileSlicing $_slicing;
    /** @var \DCarbone\PHPFHIR\Profiles\ProfileConstraint[] */
    private array $_constraints = [];
    private bool $_mustSupport;
    private null|string $_contentReference;
    private null|string $_short;
    private null|string $_definition;

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        foreach (['id', 'path'] as $k) {
            if (!isset($data[$k]) || !is_string($data[$k]) || '' === $data[$k]) {
                throw new ProfileDefinitionException(sprintf('Element is missing required string field "%s".', $k));
            }
        }
        $e = new self();
        $e->_id = $data['id'];
        $e->_path = $data['path'];
        $e->_sliceName = isset($data['sliceName']) && is_string($data['sliceName']) ? $data['sliceName'] : null;
        $e->_min = isset($data['min']) ? self::_cardinality($data['min'], $e->_id) : null;
        $e->_max = isset($data['max']) ? self::_max($data['max'], $e->_id) : null;
        foreach ($data['type'] ?? [] as $type) {
            // system primitive types (e.g. Element.id) carry their code only as an extension on "_code".
            if (is_array($type) && !isset($type['code']) && isset($type['_code'])) {
                continue;
            }
            $e->_types[] = ProfileElementType::fromArray((array)$type);
        }
        $e->_fixedValue = ProfileFixedValue::fromElementArray($data);
        $e->_binding = isset($data['binding']) ? ProfileBinding::fromArray((array)$data['binding']) : null;
        $e->_slicing = isset($data['slicing']) ? ProfileSlicing::fromArray((array)$data['slicing']) : null;
        foreach ($data['constraint'] ?? [] as $constraint) {
            $e->_constraints[] = ProfileConstraint::fromArray((array)$constraint);
        }
        $e->_mustSupport = (bool)($data['mustSupport'] ?? false);
        $e->_contentReference = isset($data['contentReference']) && is_string($data['contentReference'])
            ? $data['contentReference']
            : null;
        $e->_short = isset($data['short']) && is_string($data['short']) ? $data['short'] : null;
        $e->_definition = isset($data['definition']) && is_string($data['definition']) ? $data['definition'] : null;
        return $e;
    }

    private static function _cardinality(mixed $value, string $id): int
    {
        if (!is_int($value) || $value < 0) {
            throw new ProfileDefinitionException(sprintf('Element "%s" has invalid "min": %s', $id, json_encode($value)));
        }
        return $value;
    }

    private static function _max(mixed $value, string $id): int
    {
        if ('*' === $value) {
            return PHPFHIR_UNLIMITED;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int)$value;
        }
        throw new ProfileDefinitionException(sprintf('Element "%s" has invalid "max": %s', $id, json_encode($value)));
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function getPath(): string
    {
        return $this->_path;
    }

    /** The path of the root element is the type name, e.g. "Patient". */
    public function isRoot(): bool
    {
        return !str_contains($this->_path, '.');
    }

    /** The last path segment, e.g. "birthDate" or "deceased[x]". */
    public function getName(): string
    {
        $pos = strrpos($this->_path, '.');
        return false === $pos ? $this->_path : substr($this->_path, $pos + 1);
    }

    public function isChoice(): bool
    {
        return str_ends_with($this->_path, '[x]');
    }

    public function getSliceName(): null|string
    {
        return $this->_sliceName;
    }

    public function isSlice(): bool
    {
        return null !== $this->_sliceName;
    }

    /** True if this element is within a slice, ex: "Observation.component:systolic.code". */
    public function isWithinSlice(): bool
    {
        return str_contains($this->_id, ':');
    }

    /** The id of the element that contains this one, or null for the root. */
    public function getParentId(): null|string
    {
        $pos = strrpos($this->_id, '.');
        return false === $pos ? null : substr($this->_id, 0, $pos);
    }

    /** @return null|int Null if not stated. */
    public function getMin(): null|int
    {
        return $this->_min;
    }

    /** @return null|int Null if not stated, {@see PHPFHIR_UNLIMITED} if "*". */
    public function getMax(): null|int
    {
        return $this->_max;
    }

    /** True if the element may not be present at all (max = 0). */
    public function isProhibited(): bool
    {
        return 0 === $this->_max;
    }

    public function isRequired(): bool
    {
        return null !== $this->_min && $this->_min > 0;
    }

    /** @return \DCarbone\PHPFHIR\Profiles\ProfileElementType[] */
    public function getTypes(): array
    {
        return $this->_types;
    }

    /** @return string[] */
    public function getTypeCodes(): array
    {
        return array_map(static fn(ProfileElementType $t): string => $t->getCode(), $this->_types);
    }

    public function getFixedValue(): null|ProfileFixedValue
    {
        return $this->_fixedValue;
    }

    public function getBinding(): null|ProfileBinding
    {
        return $this->_binding;
    }

    /** Non-null on the element that defines how a repeating element is sliced. */
    public function getSlicing(): null|ProfileSlicing
    {
        return $this->_slicing;
    }

    /** @return \DCarbone\PHPFHIR\Profiles\ProfileConstraint[] */
    public function getConstraints(): array
    {
        return $this->_constraints;
    }

    public function isMustSupport(): bool
    {
        return $this->_mustSupport;
    }

    public function getContentReference(): null|string
    {
        return $this->_contentReference;
    }

    public function getShort(): null|string
    {
        return $this->_short;
    }

    public function getDefinition(): null|string
    {
        return $this->_definition;
    }
}
