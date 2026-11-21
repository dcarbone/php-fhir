# Cross-Version Translation

Status: **Implementation in progress**

## Goal

Allow a generated FHIR resource instance from one FHIR version to be translated into the equivalent
resource in any other generated FHIR version, in either direction, e.g. DSTU1 `Patient` → R5 `Patient`
or R5 `Patient` → R4 `Patient`.

Hard requirements:

1. The core engine must be **very simple**.
2. The generator ships a usable **default translation** for every hop.
3. Users must be able to **override, wrap, or replace** any part of the default translation with
   minimal ceremony, because every implementer has different opinions about what maps where.

## Decisions

| Topic | Decision |
|---|---|
| Input / output contract | Typed object → typed object. The generated namespace identifies the source version; `_getFHIRVersion()` validates its FHIR release. Raw JSON/XML has no version marker and must be parsed with a caller-selected version first. |
| Hop strategy | Adjacent hops are automatically chained. A configured non-adjacent xver package also generates a direct map in both directions; an exact map takes precedence over chaining. For example, configuring R5 → R4 makes R4 → R5 direct too, bypassing maps on the R4 → R4B → R5 chain. |
| Default rules source (phase 1) | Generator-time type diff between the two versions' definitions (removed types), plus run-time comparison of source and target field metadata. |
| xver package support | Locally configured xver packages contribute directional code mappings for `Coding` values and typed extension definitions for fields the target version lacks. Structural field translation remains name-based; FML StructureMap ingestion is out of scope. |
| Unmappable data | Removed types are listed in the generated hop map; fields absent in the target are detected at run time. Both are unmappable. The configured policy handles them; `EXTENSION` emits the official HL7 xver extension when the hop map has a definition for the field and throws otherwise. |
| Field metadata | Reflection over the existing `#[FHIRType]` property attributes on generated type classes (type class from the attribute, collection-ness from the `array` property type). Results are cached per class. |
| User extensibility | Plain PHP classes implementing `TypeTranslatorInterface`, registered on the `Translator` per `(from, to, typeName)`. A user translator may decorate the generated default. |
| Field discovery | Constructor-injected `FieldMetadataFactoryInterface`; the default `FieldMetadataFactory` reflects generated attributes and caches descriptors per factory instance. |
| Code location | Core engine (interfaces, enums, `Translator`, generic translator) always generated under `Translation/`. Per-hop default maps are generated for adjacent pairs and configured package hops. |
| Extension behavior | Emit an official xver definition when the hop map has one and the target can carry every value; otherwise throw an explicit missing-definition exception. Matching extensions are unfolded when translating back. Do not invent a JSON-string payload or claim it is an HL7 xver representation. |
| Source version lookup | A generated registry resolves a resource’s generated version namespace to a `VersionInterface`, then validates that its FHIR enum matches the resource. This supports variants that share an enum. |
| Direct-hop routing | An exact generated hop map or direct user-registered translator takes precedence over automatic intermediate-version chaining. |
| Duplicate-version routing | Automatic chaining rejects ambiguous routes when multiple generated variants occupy an intermediate FHIR release; an exact generated hop map or direct user-registered translator for the root type can override that route. |

### HL7 xver preservation constraint

HL7's Cross-Version Extensions are typed StructureDefinitions, not a generic serialization envelope.
They model version-specific content with target-compatible `value[x]` types and nested extensions
for complex structures. FHIR's extension invariant also disallows an extension from having both a
value and child extensions. For example, the R5-to-R4 `Extension.value[x]` definition uses named
child extensions for types unavailable in R4 and documents how complex `CodeableReference` data is
represented; it does not prescribe putting arbitrary source JSON in `valueString`.

Consequently, a schema diff alone cannot construct a standards-conformant xver extension for every
removed field. The generator therefore reads the extension StructureDefinitions of each configured
`hl7.fhir.uv.xver-<source>.<target>` package (`Translation\XverExtensionReader`) and renders one typed
`XverExtension` descriptor per resolvable source field into the hop map. `EXTENSION` emits only from
those descriptors and throws `MissingXverDefinitionException` for any field without one. Users can also
register a custom translator for uncovered cases.

An xver package describes the *source* version's elements as extensions usable in the *target* version,
so the extension URL carries the source release (for example
`http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency` for R5 → R4).

Known limitations:

- Unfolding is conservative: it restores only extensions whose URL, modifier status, value type, and
  content match the configured definition and target field. If the field is already populated or
  the extension has extra or unrecognized content, the extension stays in place without a notice.
- To preserve fields emitted in both directions, configure the corresponding xver package for each
  direction. Unfolding itself uses the package for the original emission direction and runs
  independently of the unmappable-field policy.
- A value whose type differs from the target's extension value types (for example `CodeableReference`
  becoming `CodeableConcept`) is not converted and falls back to the unmappable policy.
- About a dozen exotic extension shapes per package are not resolved and are skipped: generic `value[x]`
  of any type, `Task`/`Transport` input and output, `Bundle.issues`, `ElementDefinition.maxValue` and
  `minValue`, `EvidenceVariable.characteristic`, and `ExpansionProfile.contained`.
- Whole removed types are not preserved; only fields are.
- Required-cardinality of nested slices is not validated.
- HL7 publishes no xver packages for DSTU1 hops, DSTU2 → STU3, or R4 → DSTU2, so those hops always
  throw under `EXTENSION`.

References:

- [HL7 xver R5-to-R4 `Extension.value[x]` definition](https://www.hl7.org/fhir/uv/xver-r5.r4/0.1.0/StructureDefinition-ext-R5-Extension.value.html)
- [FHIR extensibility and extension structure](https://hl7.org/fhir/extensibility.html)

## Public API

```php
use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Translation\TranslationConfig;
use DCarbone\PHPFHIRGenerated\Translation\OnUnmappableFieldEnum;
use DCarbone\PHPFHIRGenerated\Versions\R5\Version as R5;

$translator = new Translator(
    new TranslationConfig(onUnmappableField: OnUnmappableFieldEnum::EXTENSION),
);

/** @var \DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIRPatient $r4Patient */
$result = $translator->translate($r4Patient, new R5());

$r5Patient = $result->getResource();
foreach ($result->getNotices() as $notice) {
    // Handle any values dropped under the configured policy.
}
```

## Core Types

All live in the generated `Translation` namespace. Names are registered as constants in
`files/constants.php` alongside the existing `PHPFHIR_*` constants.

### `TypeTranslatorInterface`

The per-type translation extension point.

```php
interface TypeTranslatorInterface
{
    /**
     * Translate one source type instance into the target version.
     * Return null to indicate the value has no representation in the target version;
     * the engine then applies the configured OnUnmappableFieldEnum policy.
     */
    public function translate(TypeInterface $source, TranslationContext $ctx): null|TypeInterface;
}
```

### `TranslationContext`

Passed to every translator call. Carries:

- `getSourceVersion(): VersionInterface`
- `getTargetVersion(): VersionInterface`
- `getConfig(): TranslationConfig`
- `getHopMap(): null|HopMapInterface` — the map for the current hop, if one was generated.
- `translate(TypeInterface $source): null|TypeInterface` — re-enters the engine for nested values so a
  user translator for `Patient` does not have to know how to translate `HumanName`.
- `getPath(): string` — dotted FHIR path of the value being translated, for notices.
- `addNotice(TranslationNotice $notice): void`

The context's versions, configuration, path, dispatcher, and notice collector are readonly
properties. Nested contexts reuse the typed `TypeTranslatorInterface` dispatcher and the
per-translation notice collector; no callbacks are required.

### `Translator`

Facade and registry.

- `translate(ResourceTypeInterface $source, VersionInterface $target): TranslationResult`
- `register(VersionInterface $from, VersionInterface $to, string $typeName, TypeTranslatorInterface $translator): static`

The constructor accepts `TranslationConfig $config = new TranslationConfig()` and
`FieldMetadataFactoryInterface $metadataFactory = new FieldMetadataFactory()`. The same factory
instance is used by the default translator for source and target metadata, nested values, and
every hop. Omitting it retains reflection-based discovery.

To customize default behavior, construct a `GenericTypeTranslator` and pass it to a
`TypeTranslatorInterface` implementation that delegates to it; register the wrapper directly.
Pass the same metadata factory to this `GenericTypeTranslator` when using custom discovery;
explicitly registered translators own their dependencies.
`register()` accepts a translator object, not a callable or factory. `TypeTranslatorDispatcher`
owns the registrations and invokes the selected translator through the interface.

`translate()` resolves the hop chain from the ordered list of generated versions, then for each hop
looks up the translator for
`(from, to, $value->_getFHIRTypeName())` in this order:

1. User registration for the exact type.
2. `GenericTypeTranslator` fallback.

A direct registration for the root type takes precedence over automatic chaining. This also lets a
user explicitly resolve an otherwise ambiguous intermediate-version route.

`TypeTranslatorDispatcher` implements `TypeTranslatorInterface`, owns the per-type registrations,
and validates each translated value's target version. A fresh `TranslationNoticeCollector` is
shared across all contexts and hops in one translation.

### `GenericTypeTranslator`

The default engine. One class, no per-type knowledge:

1. Apply the current hop's removed-type rule, then resolve the target class via the
   target `VersionTypeMap::getTypeClassname()`. When the parent field's target type is known,
   `TranslationContext::getExpectedTargetClass()` overrides the name-based lookup, so a value is
   coerced to the type the target field declares (for example, R4 `code` into an R5
   `AdministrativeGender` field). If no target class exists, return `null`.
2. If the source implements `LeafTypeInterface`, copy `$source->getValue()` into the target.
   This empty marker extends `TypeInterface` and is shared by primitives (through
   `PrimitiveTypeInterface`) and XHTML. Primitives retain their `ValueContainerTypeInterface`
   contract; XHTML retains its dedicated XML and JSON representation. The generator uses
   `Type::isLeafType()` for shared leaf handling, excluding primitive containers.
3. Otherwise, instantiate the target class and copy fields with the same name discovered through
   `FieldMetadataFactory`, recursively translating nested `TypeInterface` values.
4. If a source field is not present on the target, or its value translates to `null`, apply the
   configured `OnUnmappableFieldEnum` policy. A non-resource element that had
   values but received none returns `null`, so the policy also applies at its parent; under `DROP`
   this produces one notice for the inner field and one for the emptied element. Resources always
   yield an instance, even if empty. Unmappable values are collected while fields are translated and
   handled afterwards, so their notices follow the notices of the translated fields. `EXTENSION`
   appends an official xver extension to the target's `modifierExtension` or `extension` when the hop
   map has a definition and every value can be carried (all-or-nothing, preserving extensions already
   present), and otherwise throws `MissingXverDefinitionException`; schema-diff generation never
   invents a placeholder URL or a non-standard JSON payload.
5. After ordinary fields and unmappable values have been handled, unfold extensions that match the
   package for the reverse hop. Unfolding is independent of the unmappable-field policy; extensions
   that do not exactly fit a field are left in place.

Choice (`[x]`) fields need no special handling: generated classes already materialize choice
variants as discrete fields (`deceasedBoolean`, `deceasedDateTime`). Resource-container fields
(`Bundle.entry.resource`, `DomainResource.contained`) store unwrapped resources, so the default
metadata factory describes them with the contained-resource interface accepted by the field's
setter (the version's `VersionContainedTypeInterface`) instead of the `ResourceContainer` wrapper.

### `FieldMetadataFactoryInterface` / `FieldMetadataFactory` / `FieldMetadata`

`FieldMetadataFactoryInterface` defines instance-based discovery:

```php
interface FieldMetadataFactoryInterface
{
    /**
     * @return array<string, \DCarbone\PHPFHIRGenerated\Translation\FieldMetadata>
     */
    public function forType(TypeInterface $type): array;
}
```

`FieldMetadataFactory` is the default implementation. It discovers fields through reflection and
caches descriptors per generated class within that factory instance. Users can replace discovery
entirely or compose a factory that delegates to the default and adjusts selected descriptors.
Factories receive the actual source and newly constructed target instances. Custom implementations
may inspect instance state and choose their own caching strategy; only the default implementation
assumes metadata is class-specific.

The generated `VersionRegistry` matches a type to its version by namespace prefix. Version
namespaces are ordered most specific first, so a nested namespace such as `R4\Custom` is matched
before `R4`. Version mismatch diagnostics use the `FHIRVersion` string form.

`FieldMetadata` is only an immutable descriptor for a field's type, collection flag, getter,
and setter. `getType()`, `isCollection()`, `getGetter()`, and `getSetter()` expose its values;
it performs no discovery or caching. The factory reads field names from property names, the
type from a `TypeInterface` attribute, and the collection flag from an `array` property type.

For each non-leaf source and target, the generic translator requests metadata, matches fields by
the returned array keys, invokes the source descriptor's getter, recursively translates values,
checks the target descriptor's type and collection flag, and invokes its setter. Keys also
determine notice paths; they need not be PHP property names in a custom factory. Missing target
keys still use the configured unmappable-field policy. Leaf values bypass field discovery.
Factory exceptions propagate to the caller. Routing and per-type translator registration remain
unchanged.

### `OnUnmappableFieldEnum`

Follows the existing `Encoding\OnUnknownProfileEnum` / `OnProfileVersionMismatchEnum` pattern.

| Case | Behavior |
|---|---|
| `EXTENSION` (default) | Emit the official xver extension from the hop map's definition. If none exists, or the target cannot carry the value, throw `MissingXverDefinitionException`. |
| `DROP` | Discard the value. Adds a notice. |
| `THROW` | Throw `UnmappableFieldException`. |

### `TranslationResult` / `TranslationNotice`

`TranslationResult` holds the translated resource and an ordered list of `TranslationNotice`
objects (`path`, `hop`, `kind`, `message`). Values dropped through the configured unmappable-field
policy emit notices, including when nested. Values preserved as xver extensions emit no notice, value
coercion notices are not implemented yet, and matching type or field names does not guarantee semantic
equivalence.

## Generated Hop Maps

For each adjacent pair `(A, B)` of configured versions, and for each configured non-adjacent package
hop, the generator emits direct hop-map classes in both directions. Only the package's declared
direction receives that package's ConceptMaps and xver definitions. Each map implements
`HopMapInterface`:

```php
interface HopMapInterface
{
    public function getSourceVersion(): VersionInterface;
    public function getTargetVersion(): VersionInterface;
    /** @return string[] */
    public function getRemovedTypes(): array;
    /** @return \DCarbone\PHPFHIRGenerated\Translation\CodeMap[] */
    public function getCodeMaps(): array;
    /**
     * Keyed by "<source type FHIR name>.<source field>".
     * @return array<string, \DCarbone\PHPFHIRGenerated\Translation\XverExtension>
     */
    public function getXverExtensions(): array;
}
```

### Local xver packages

The generator can ingest ConceptMaps and extension definitions from local FHIR NPM package directories
or `.tgz` archives.
Configure each package at the top level, explicitly naming its direction:

```php
use DCarbone\PHPFHIR\Config;

$config = Config::fromArray([
    'libraryPath' => 'generated/src',
    'versions' => [
        ['name' => 'R4', 'schemaPath' => 'schemas/r4'],
        ['name' => 'R5', 'schemaPath' => 'schemas/r5'],
    ],
    'translationPackages' => [
        [
            'sourceVersion' => 'R4',
            'targetVersion' => 'R5',
            'source' => 'packages/hl7.fhir.uv.xver-r4.r5.tgz',
        ],
    ],
]);
```

The package source must be readable and contain `package.json`. Configure reverse-direction
packages separately; mappings are never inverted automatically. A package is applied only when
both versions are selected for generation. Adjacent pairs contribute to the ordinary two-way hop
maps; a non-adjacent package pair adds direct maps in both directions, with package mappings only in
the declared direction. An exact generated map is used before automatic chaining through
intermediate versions.

At runtime, a map is applied only to `Coding` values that have both an explicit `system` and a
`code`, including `Coding` values nested in `CodeableConcept.coding`. The `group.source` and
`group.target` systems must match the coding's source system, and the ConceptMap must provide
exactly one distinct target marked `equivalent` (using R4 `equivalence` or newer
`relationship` fields). When several ConceptMaps share a source system (for example
`fhir-types` mapped to both `data-types` and `resource-types`), only maps that translate the code
to a non-null target are candidates; the code is mapped when exactly one distinct
`targetSystem|code` candidate remains. The translator updates both the target system and code. Missing,
ambiguous, and non-equivalent mappings are handled by the configured unmappable-field policy.
If no map applies to a source system, the `Coding` is copied normally; when a hop has no code
maps, `Coding` values are not inspected at all. When a hop does have code maps, `Coding.system`
and `Coding.code` are read by unwrapping their primitive containers (element → primitive →
scalar) before the value is validated as a string. ConceptMap codes are always strings, including
numeric codes such as `"123"`. Bare `code` primitives
remain unchanged because their binding system is not available in runtime field metadata; use a
custom type translator for those (see
`tests/Integration/Translation/Fixtures/R5ToR4EncounterStatusTranslator.php`).

This support does not translate structure, infer field renames, or execute FML/StructureMap.
`HopMapInterface::getCodeMaps()` exposes generated typed `CodeMap` instances; generated hop maps
lazily cache these objects.

Schema-diff derivation (computed in the generator from `Version\Definition\Type` and
`Properties` of both versions):

- Type present in both versions → no entry (generic `Keep` behavior applies).
- Type absent in target → entry in a "removed types" list; instances translate to `null` and hit the
  unmappable policy. Value-set enumeration types (`X-list` in R4, `XEnum` in R4B/R5) are never
  listed: their names differ between releases, but the generic translator maps them to the target
  version's enumeration class through the declared field type.
- Fields are not diffed by the generator. The generic translator compares source and target field
  metadata at run time: a field absent in the target goes through the configured
  unmappable-field policy, and a field present only in the target is left unset. Only extensions
  defined by a configured xver package are emitted; no placeholder URL is ever generated.
- Type renames are not modelled: a type is matched to the target version's type of the same name,
  or to the declared field type when one exists. A custom translator can handle a rename.

`HopMapBuilder` returns an immutable `HopMapDefinition` containing the removed-type list and any
configured code maps and xver extension definitions. The generator renders that definition through `template/translation/class_hop_map.php`.

`Translator` discovers hop maps through a generated `Translation/HopMapRegistry`, so routing needs no
reflection or filesystem scanning. The generator emits both directions for each adjacent pair and
for each configured non-adjacent package hop. Only the package's declared direction receives its
ConceptMaps and xver definitions; the reverse map provides a direct route and lets the extension
unfolder find definitions from the original emitting hop. A directly registered root-type
translator takes precedence; otherwise, an exact generated hop map is used before automatic
intermediate-version chaining. A direct non-adjacent map bypasses any code maps contributed only by
intermediate hops. The generator adds every map path to the standalone autoloader, so Composer is
not required to load hop maps.

## Version Coverage Notes

| Hop | Default quality |
|---|---|
| DSTU1 ↔ DSTU2 | Schema diff only; HL7 publishes no mapping artifacts. Expect many extension-preserved fields. |
| DSTU2 ↔ STU3 | Schema diff; configured packages may add directional `Coding` mappings. |
| STU3 ↔ R4 | Schema diff; configured packages may add directional `Coding` mappings. |
| R4 ↔ R4B ↔ R5 | Schema diff; configured packages may add directional `Coding` mappings. |
| R5 ↔ R6 | Schema diff; configured packages may add directional `Coding` mappings when available. |

## Implementation Phases

1. **Implemented: core engine templates** under `template/core/translation/`: interfaces, enums,
   `Translator`, `TypeTranslatorDispatcher`, `GenericTypeTranslator`, `FieldMetadataFactory`,
   `FieldMetadataFactoryInterface`, `FieldMetadata`, `TranslationContext`, `TranslationNoticeCollector`,
   `TranslationResult`, exceptions, and the generated version registry. Core template discovery is
   automatic. Adjacent-version chaining,
   custom direct-route overrides, and per-translation notice collection are in place. Custom
   translators are registered as typed objects; there is no callable/factory registration API.
2. **Implemented: schema diff + hop map generation**: `Translation\HopMapBuilder` diffs version
   definitions and renders `Translation/Maps/*.php` and `HopMapRegistry` for adjacent configured
   version pairs. Removed fields and types are recorded as unmappable, leaving the existing
   configured policy in control; no placeholder xver URLs are emitted.
3. **Implemented: runtime coverage**: tests cover `GenericTypeTranslator`, chaining and reverse
   routing, direct registrations and wrappers, nested notice propagation, and unmappable-field
   policies.
4. **Implemented: documentation**: this design reference and the wiki guide include a worked
   translation example and typed custom-translator wrapper.
5. **Implemented: local ConceptMap ingestion for coded values**: configured package resources
   contribute directional `Coding.system`/`Coding.code` mappings to adjacent hop maps. Structural
   translation remains unchanged; unmatched or ambiguous code mappings use the existing policy.
6. **Implemented: xver extension emission**: `Translation\XverExtensionReader` turns each package's
   extension StructureDefinitions into typed `XverExtensionDefinition`s, which the generator renders
   into the hop map as `XverExtension` descriptors. `XverExtensionEmitter` builds the extensions at
   run time under the `EXTENSION` policy.
7. **Implemented: xver extension unfolding**: `XverExtensionUnfolder` restores compatible values
   from extensions emitted by the reverse hop, including nested complex values and enum-bound codes.
   It leaves extensions in place when they do not match exactly. Configured non-adjacent package
   hops get a direct map in both directions.

## Testing

- `phpunit/Builder.xml` covers rendering and runtime behavior against small fixture types.
- `phpunit/Translation.xml` generates every version (DSTU1–R5) plus the R5→R4, R4→STU3, and
  STU3→R4 xver packages and runs the integration tests in `tests/Integration/Translation`: chained
  Patient translation in both directions, unmappable-field policies, ConceptMap coding translation,
  xver emission and round-trip unfolding, and custom translators and metadata factories. It requires
  the XSDs in `input/<Version>` and `input/profiles/hl7.fhir.uv.xver-{r5.r4,r4.r3,r3.r4}/package.tgz`;
  CI runs it on PHP 8.1–8.5.

## Out of Scope

- FML / StructureMap execution.
- Terminology translation beyond the configured ConceptMap `Coding` mappings described above.
- Translating raw JSON/XML without first parsing it into a typed resource (a thin
  `translateRaw($raw, $from, $to)` convenience may be added later on top of the typed API).
