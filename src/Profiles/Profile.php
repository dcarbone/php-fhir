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
 * A FHIR profile: a StructureDefinition with a "constraint" derivation.
 *
 * Elements come in two flavours: {@see getDifferentialElements()} are only the things this profile changes relative
 * to its base, while {@see getSnapshotElements()} (when the source provides them) are the fully resolved element list
 * including everything inherited.
 */
class Profile
{
    private string $_id;
    private string $_url;
    private string $_name;
    private null|string $_title;
    private null|string $_version;
    private null|string $_copyright;
    private null|string $_status;
    private null|string $_description;
    private string $_type;
    private string $_kind;
    private string $_baseDefinition;
    private bool $_abstract;
    /** @var array<array{type: string, expression: string}> */
    private array $_context = [];
    /** @var array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement> */
    private array $_differential = [];
    /** @var null|array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement> */
    private null|array $_snapshot = null;

    private null|Profile $_parent = null;

    private function __construct()
    {
    }

    /**
     * @throws \DCarbone\PHPFHIR\Profiles\ProfileDefinitionException
     */
    public static function fromStructureDefinition(array $sd): self
    {
        $id = isset($sd['id']) && is_string($sd['id']) ? $sd['id'] : '(unknown id)';
        foreach (['id', 'url', 'name', 'type', 'kind', 'baseDefinition', 'derivation'] as $k) {
            if (!isset($sd[$k]) || !is_string($sd[$k]) || '' === $sd[$k]) {
                throw new ProfileDefinitionException(sprintf(
                    'StructureDefinition "%s" is missing required string field "%s".',
                    $id,
                    $k,
                ));
            }
        }
        if ('StructureDefinition' !== ($sd['resourceType'] ?? null)) {
            throw new ProfileDefinitionException(sprintf('"%s" is not a StructureDefinition.', $id));
        }
        if ('constraint' !== $sd['derivation']) {
            throw new ProfileDefinitionException(sprintf(
                'StructureDefinition "%s" has derivation "%s", only "constraint" is a profile.',
                $id,
                $sd['derivation'],
            ));
        }

        $p = new self();
        $p->_id = $sd['id'];
        $p->_url = $sd['url'];
        $p->_name = $sd['name'];
        $p->_title = isset($sd['title']) && is_string($sd['title']) ? $sd['title'] : null;
        $p->_copyright = isset($sd['copyright']) && is_string($sd['copyright']) && '' !== trim($sd['copyright']) ? trim($sd['copyright']) : null;
        $p->_version = isset($sd['version']) && is_string($sd['version']) ? $sd['version'] : null;
        $p->_status = isset($sd['status']) && is_string($sd['status']) ? $sd['status'] : null;
        $p->_description = isset($sd['description']) && is_string($sd['description']) ? $sd['description'] : null;
        $p->_type = $sd['type'];
        $p->_kind = $sd['kind'];
        $p->_baseDefinition = $sd['baseDefinition'];
        $p->_abstract = (bool)($sd['abstract'] ?? false);
        foreach ($sd['context'] ?? [] as $ctx) {
            if (isset($ctx['type'], $ctx['expression']) && is_string($ctx['type']) && is_string($ctx['expression'])) {
                $p->_context[] = ['type' => $ctx['type'], 'expression' => $ctx['expression']];
            }
        }

        $p->_differential = self::_elements($sd['differential']['element'] ?? [], $id);
        if (isset($sd['snapshot']['element'])) {
            $p->_snapshot = self::_elements($sd['snapshot']['element'], $id);
        }
        if ([] === $p->_differential && null === $p->_snapshot) {
            // an unconstrained profile is legal, but there must be at least one element list.
            if (!isset($sd['differential']) && !isset($sd['snapshot'])) {
                throw new ProfileDefinitionException(sprintf(
                    'StructureDefinition "%s" has neither a snapshot nor a differential.',
                    $id,
                ));
            }
        }
        return $p;
    }

    /**
     * @return array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement>
     */
    private static function _elements(mixed $raw, string $profileId): array
    {
        if (!is_array($raw)) {
            throw new ProfileDefinitionException(sprintf('StructureDefinition "%s" element list is not an array.', $profileId));
        }
        $out = [];
        foreach ($raw as $data) {
            try {
                $element = ProfileElement::fromArray((array)$data);
            } catch (ProfileDefinitionException $e) {
                throw new ProfileDefinitionException(
                    sprintf('StructureDefinition "%s": %s', $profileId, $e->getMessage()),
                    0,
                    $e,
                );
            }
            // some published packages repeat slice entries; the first one wins.
            if (isset($out[$element->getId()])) {
                continue;
            }
            $out[$element->getId()] = $element;
        }
        return $out;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function getUrl(): string
    {
        return $this->_url;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getTitle(): null|string
    {
        return $this->_title;
    }

    public function getCopyright(): null|string
    {
        return $this->_copyright;
    }

    public function getVersion(): null|string
    {
        return $this->_version;
    }

    public function getStatus(): null|string
    {
        return $this->_status;
    }

    public function getDescription(): null|string
    {
        return $this->_description;
    }

    /** The FHIR type this profile ultimately constrains, e.g. "Patient" or "Extension". */
    public function getType(): string
    {
        return $this->_type;
    }

    /** @return string "resource", "complex-type" or "primitive-type" */
    public function getKind(): string
    {
        return $this->_kind;
    }

    public function isExtension(): bool
    {
        return 'Extension' === $this->_type;
    }

    public function isAbstract(): bool
    {
        return $this->_abstract;
    }

    public function getBaseDefinition(): string
    {
        return $this->_baseDefinition;
    }

    /**
     * Where an extension profile may be used.
     *
     * @return array<array{type: string, expression: string}>
     */
    public function getContext(): array
    {
        return $this->_context;
    }

    /** @return array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement> Indexed by element id. */
    public function getDifferentialElements(): array
    {
        return $this->_differential;
    }

    public function hasSnapshot(): bool
    {
        return null !== $this->_snapshot;
    }

    /** @return array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement> Indexed by element id.  Empty if there is no snapshot. */
    public function getSnapshotElements(): array
    {
        return $this->_snapshot ?? [];
    }

    /**
     * The most complete element list available: the snapshot if there is one, otherwise the differential.
     *
     * @return array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement>
     */
    public function getElements(): array
    {
        return $this->_snapshot ?? $this->_differential;
    }

    public function getElement(string $id): null|ProfileElement
    {
        return $this->getElements()[$id] ?? null;
    }

    public function getDifferentialElement(string $id): null|ProfileElement
    {
        return $this->_differential[$id] ?? null;
    }

    /**
     * Direct children of the element with the provided id.
     *
     * @return \DCarbone\PHPFHIR\Profiles\ProfileElement[]
     */
    public function getChildren(string $id): array
    {
        $out = [];
        foreach ($this->getElements() as $element) {
            if ($element->getParentId() === $id) {
                $out[] = $element;
            }
        }
        return $out;
    }

    /**
     * The slices of the element with the provided id, e.g. "Patient.extension" => "Patient.extension:race", etc.
     *
     * @return array<string, \DCarbone\PHPFHIR\Profiles\ProfileElement> Indexed by slice name.
     */
    public function getSlices(string $id): array
    {
        $out = [];
        $prefix = $id . ':';
        foreach ($this->getElements() as $element) {
            if ($element->isSlice() && str_starts_with($element->getId(), $prefix)
                && !str_contains(substr($element->getId(), strlen($prefix)), '.')) {
                $out[$element->getSliceName()] = $element;
            }
        }
        return $out;
    }

    /**
     * The profile this one is derived from, if that is another profile and it could be found.  Null if this profile
     * constrains a base FHIR type directly, or if its base profile could not be located.
     */
    public function getParent(): null|Profile
    {
        return $this->_parent;
    }

    public function hasParent(): bool
    {
        return null !== $this->_parent;
    }

    /**
     * @internal Set by {@see \DCarbone\PHPFHIR\Profiles\ProfileSet}.
     */
    public function setParent(null|Profile $parent): void
    {
        $this->_parent = $parent;
    }

    /**
     * Parent profiles, nearest first.
     *
     * @return \DCarbone\PHPFHIR\Profiles\Profile[]
     */
    public function getAncestors(): array
    {
        $out = [];
        for ($p = $this->_parent; null !== $p; $p = $p->_parent) {
            $out[] = $p;
        }
        return $out;
    }

    /**
     * True if this profile's base definition is the base FHIR type itself, as opposed to another profile.
     */
    public function isBasedOnBaseType(): bool
    {
        return 'http://hl7.org/fhir/StructureDefinition/' . $this->_type === ProfilePackage::stripCanonicalVersion($this->_baseDefinition);
    }
}
