<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Profiles;

/*
 * Copyright 2025-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Profiles\Profile;
use DCarbone\PHPFHIR\Profiles\ProfileClass;
use DCarbone\PHPFHIR\Profiles\ProfilePhpExpression;
use DCarbone\PHPFHIR\Profiles\ProfileProperty;
use DCarbone\PHPFHIR\Profiles\ProfileRules;
use DCarbone\PHPFHIR\Profiles\ProfileTypeNavigator;
use DCarbone\PHPFHIR\Profiles\ProfileValidationCode;
use DCarbone\PHPFHIR\Profiles\ProfileValidationGenerator;
use DCarbone\PHPFHIR\Render\Templates;
use PHPUnit\Framework\TestCase;

/**
 * Checks the rule map generated for a profile against fake type definitions.  Running the rules is the job of the
 * generated validation system, which has its own tests; what matters here is that every constraint ends up under the
 * right path, with the right rule and constraint.
 */
class ProfileValidationTest extends TestCase
{
    use PackageFixtures;

    private const _NS = 'Acme\\Val\\R4\\Profiles';
    private const _T = 'Acme\\Val\\R4\\Types\\';

    private static function prop(string $field, bool $collection, string $typeKey, ProfilePropertyKindEnum $kind = ProfilePropertyKindEnum::COMPLEX): ProfileProperty
    {
        return new ProfileProperty($field, 'get' . ucfirst($field), $collection, $typeKey, self::_T . 'FHIR' . ucfirst($typeKey), $kind);
    }

    private static function navigator(): ProfileTypeNavigator
    {
        $c = ProfilePropertyKindEnum::CONTAINER;
        $p = ProfilePropertyKindEnum::PRIMITIVE;
        $types = [
            'Patient' => [
                'name' => self::prop('name', true, 'HumanName'),
                'photo' => self::prop('photo', true, 'Attachment'),
                'language' => self::prop('language', false, 'code', $c),
                'gender' => self::prop('gender', false, 'code', $c),
                'maritalStatus' => self::prop('maritalStatus', false, 'CodeableConcept'),
                'contact' => self::prop('contact', true, 'PatientContact'),
                'extension' => self::prop('extension', true, 'Extension'),
                'identifier' => self::prop('identifier', true, 'Identifier'),
            ],
            'HumanName' => ['family' => self::prop('family', false, 'string', $c)],
            'PatientContact' => ['relationship' => self::prop('relationship', true, 'CodeableConcept')],
            'CodeableConcept' => ['coding' => self::prop('coding', true, 'Coding')],
            'Coding' => ['system' => self::prop('system', false, 'uri', $c), 'code' => self::prop('code', false, 'code', $c)],
            'Extension' => ['url' => self::prop('url', false, 'uri', $p)],
            'Identifier' => ['system' => self::prop('system', false, 'uri', $c)],
        ];
        $choices = [
            'deceasedBoolean' => self::prop('deceasedBoolean', false, 'boolean', $c),
            'deceasedDateTime' => self::prop('deceasedDateTime', false, 'dateTime', $c),
            'deceasedInteger' => self::prop('deceasedInteger', false, 'integer', $c),
        ];

        return new class($types, $choices) implements ProfileTypeNavigator {
            private array $types;
            private array $choices;

            public function __construct(array $types, array $choices)
            {
                $this->types = $types;
                $this->choices = $choices;
            }

            public function getProperty(string $typeKey, string $field): null|ProfileProperty
            {
                return $this->types[$typeKey][$field] ?? null;
            }

            public function getChoiceProperties(string $typeKey, string $base): array
            {
                return 'Patient' === $typeKey && 'deceased' === $base ? array_values($this->choices) : [];
            }
        };
    }

    private static function generate(array $rules = [], array $slices = []): ProfileValidationCode
    {
        return ProfileValidationGenerator::generate(
            ['rules' => $rules, 'slices' => $slices, 'invariants' => [], 'skipped' => []],
            'Patient',
            self::navigator(),
            new Imports(null, self::_NS . '\\Ig', 'Sample'),
        );
    }

    public function testTopLevelElementsAreKeyedByFieldConstant(): void
    {
        $code = self::generate([
            ['path' => 'name', 'min' => 1],
            ['path' => 'photo', 'max' => 0],
            ['path' => 'contact', 'max' => 2],
        ]);
        $this->assertSame([
            'self::FIELD_NAME' => ['min_occurs' => 1],
            'self::FIELD_PHOTO' => ['max_occurs' => 0],
            'self::FIELD_CONTACT' => ['max_occurs' => 2],
        ], $code->rules);
        $this->assertSame([], $code->skipped);
    }

    public function testNestedElementsAreKeyedByPath(): void
    {
        $code = self::generate([
            ['path' => 'name.family', 'min' => 1, 'max' => 1],
            ['path' => 'contact.relationship', 'codes' => [['http://r', 'C']]],
        ]);
        $this->assertSame([
            "'name.family'" => ['min_occurs' => 1],
            "'contact.relationship'" => ['code_binding' => [['http://r', 'C']]],
        ], $code->rules);
    }

    public function testSingleValuedMaximumOfOneAddsNothing(): void
    {
        $this->assertSame([], self::generate([['path' => 'maritalStatus', 'max' => 1]])->rules);
    }

    public function testChoiceElement(): void
    {
        $code = self::generate([['path' => 'deceased[x]', 'max' => 1, 'types' => ['boolean', 'dateTime']]]);
        $this->assertSame([
            "'deceased[x]'" => [
                'choice_occurs' => [
                    'fields' => ['deceasedBoolean', 'deceasedDateTime', 'deceasedInteger'],
                    'allowed' => ['deceasedBoolean', 'deceasedDateTime'],
                    'max' => 1,
                ],
            ],
        ], $code->rules);
    }

    public function testChoiceElementAllowingEveryVariantOmitsAllowed(): void
    {
        $code = self::generate([['path' => 'deceased[x]', 'max' => 1, 'types' => ['boolean', 'dateTime', 'integer']]]);
        $this->assertSame([
            "'deceased[x]'" => [
                'choice_occurs' => [
                    'fields' => ['deceasedBoolean', 'deceasedDateTime', 'deceasedInteger'],
                    'max' => 1,
                ],
            ],
        ], $code->rules);
        $this->assertSame([], self::generate([['path' => 'deceased[x]', 'types' => ['boolean', 'dateTime', 'integer']]])->rules);
    }

    public function testFixedPatternAndBinding(): void
    {
        $pattern = ['coding' => [['system' => 's', 'code' => 'M']]];
        $code = self::generate([
            ['path' => 'language', 'fixed' => 'en'],
            ['path' => 'maritalStatus', 'pattern' => $pattern],
            ['path' => 'gender', 'codes' => [['http://g', 'male']]],
        ]);
        $this->assertSame([
            'self::FIELD_LANGUAGE' => ['fixed_value' => 'en'],
            'self::FIELD_MARITAL_STATUS' => ['pattern_value' => $pattern],
            'self::FIELD_GENDER' => ['code_binding' => [['http://g', 'male']]],
        ], $code->rules);
    }

    public function testSlicesShareTheirMatchConstantWithTheRules(): void
    {
        $code = self::generate([], [
            ['path' => 'extension', 'name' => 'race', 'match' => ['url' => 'http://x/race'], 'max' => 1],
            ['path' => 'identifier', 'name' => 'ssn', 'match' => ['system' => 'http://ssa.gov'], 'min' => 1, 'max' => 2],
            ['path' => 'identifier', 'name' => 'other', 'match' => ['system' => 'http://other']],
        ]);
        $this->assertEquals([
            'self::FIELD_EXTENSION' => ['slice_occurs' => [
                ['name' => 'race', 'match' => new ProfilePhpExpression('self::_SLICE_MATCH_0'), 'max' => 1],
            ]],
            'self::FIELD_IDENTIFIER' => ['slice_occurs' => [
                ['name' => 'ssn', 'match' => new ProfilePhpExpression('self::_SLICE_MATCH_1'), 'min' => 1, 'max' => 2],
            ]],
        ], $code->rules);
        $this->assertSame(['_SLICE_MATCH_0', '_SLICE_MATCH_1', '_SLICE_MATCH_2'], array_column($code->sliceMatchers, 'constant'));
        $this->assertStringContainsString("private const _SLICE_MATCH_1 = [\n        'system' => 'http://ssa.gov',\n    ];", $code->constants);
        $this->assertSame([], $code->skipped);
    }

    public function testUnresolvableConstraintsAreSkipped(): void
    {
        $code = self::generate([
            ['path' => 'nope', 'min' => 1],
            ['path' => 'maritalStatus', 'pattern' => ['nope' => 'x']],
            ['path' => 'language', 'fixed' => ['coding' => 'x']],
            ['path' => 'deceased[x]', 'min' => 1, 'fixed' => 'x'],
        ], [
            ['path' => 'identifier', 'name' => 'bad', 'match' => ['nope' => 'x'], 'min' => 1],
        ]);
        $this->assertSame([
            "'deceased[x]'" => ['choice_occurs' => ['fields' => ['deceasedBoolean', 'deceasedDateTime', 'deceasedInteger'], 'min' => 1]],
        ], $code->rules);
        $this->assertCount(5, $code->skipped, var_export($code->skipped, true));
        $this->assertSame([], $code->sliceMatchers);
    }

    public function testRuleMapExport(): void
    {
        $code = self::generate([['path' => 'name', 'min' => 1], ['path' => 'name.family', 'min' => 1]]);
        $this->assertSame(
            "[\n    self::FIELD_NAME => [\n        'min_occurs' => 1,\n    ],\n    'name.family' => [\n        'min_occurs' => 1,\n    ],\n]",
            ProfileRules::exportFieldRules($code->rules, 0),
        );
    }

    public function testRenderedClassUsesRulesAndTightening(): void
    {
        $compiled = [
            'rules' => [['path' => 'name', 'min' => 1], ['path' => 'name.family', 'min' => 1]],
            'slices' => [['path' => 'identifier', 'name' => 'ssn', 'match' => ['system' => 'http://ssa.gov'], 'min' => 1, 'max' => 1]],
            'invariants' => [],
            'skipped' => [],
        ];
        $profile = Profile::fromStructureDefinition(self::structureDefinition('sample', 'Patient'));
        $support = [
            'matcher' => 'Acme\\Val\\R4\\Validation\\ValueMatcher',
            'validator' => 'Acme\\Val\\R4\\Validation\\Validator',
            'profileInterface' => 'Acme\\Val\\Types\\ProfileTypeInterface',
        ];
        $class = new ProfileClass($profile, self::_NS . '\\Ig', 'Sample', self::_T . 'FHIRPatient', null, self::_NS, $compiled, self::navigator(), [], $support);
        $source = Templates::renderProfileClass('/* c */', $class);

        // must parse
        token_get_all($source, TOKEN_PARSE);
        $this->assertStringContainsString('use Acme\\Val\\R4\\Validation\\ValueMatcher;', $source);
        $this->assertStringContainsString('use Acme\\Val\\R4\\Validation\\Validator;', $source);
        $this->assertStringContainsString("self::FIELD_NAME => [\n            'min_occurs' => 1,", $source);
        $this->assertStringContainsString("'name.family' => [", $source);
        $this->assertStringContainsString('Validator::tighten(', $source);
        $this->assertStringContainsString('public function getSsnIdentifier(): null|FHIRIdentifier', $source);
        $this->assertStringContainsString('ValueMatcher::matches($item, self::_SLICE_MATCH_0)', $source);
        $this->assertStringNotContainsString('function _getValidationErrors', $source);

        // the root of a profile hierarchy implements the profile interface and runs the profile's own rules.
        $this->assertStringContainsString('use Acme\\Val\\Types\\ProfileTypeInterface;', $source);
        $this->assertStringContainsString('class Sample extends FHIRPatient implements ProfileTypeInterface', $source);
        $this->assertStringContainsString('public function _getProfileValidationErrors(): array', $source);
        $this->assertStringContainsString('$this->_collectValidationErrors($rules, false)', $source);
        $this->assertStringContainsString("protected static function _getProfileValidationRuleNames(): array\n    {\n        \$out = [];", $source);

        // a derived profile inherits both, adding its own rules to its parent's.
        $child = new ProfileClass($profile, self::_NS . '\\Ig', 'Child', self::_T . 'FHIRPatient', $class, self::_NS, $compiled, self::navigator(), [], $support);
        $source = Templates::renderProfileClass('/* c */', $child);
        token_get_all($source, TOKEN_PARSE);
        $this->assertStringNotContainsString('ProfileTypeInterface', $source);
        $this->assertStringNotContainsString('function _getProfileValidationErrors', $source);
        $this->assertStringContainsString('$out = parent::_getProfileValidationRuleNames();', $source);
    }

    public function testMetadataConstants(): void
    {
        $profile = Profile::fromStructureDefinition(self::structureDefinition('sample', 'Patient'));
        $class = new ProfileClass($profile, self::_NS . '\\Ig', 'Sample', self::_T . 'FHIRPatient', null, self::_NS, ['rules' => [], 'slices' => [], 'invariants' => [], 'skipped' => []], self::navigator());
        $source = Templates::renderProfileClass('/* c */', $class);
        token_get_all($source, TOKEN_PARSE);
        $this->assertStringContainsString("PROFILE_URL = 'http://example.org/fhir/StructureDefinition/sample'", $source);
    }

    public function testSliceAccessorOverrideKeepsInheritedShape(): void
    {
        $slice = static fn(string $name, null|int $max): array => ['path' => 'identifier', 'name' => $name, 'match' => ['system' => 'http://' . $name]] + (null === $max ? [] : ['max' => $max]);
        $compiled = static fn(array ...$slices): array => ['rules' => [], 'slices' => $slices, 'invariants' => [], 'skipped' => []];
        $profile = Profile::fromStructureDefinition(self::structureDefinition('sample', 'Patient'));
        $parent = new ProfileClass($profile, self::_NS . '\\Ig', 'Base', self::_T . 'FHIRPatient', null, self::_NS, $compiled($slice('mrn', null)), self::navigator());
        $child = new ProfileClass($profile, self::_NS . '\\Ig', 'Child', self::_T . 'FHIRPatient', $parent, self::_NS, $compiled($slice('mrn', 1), $slice('ssn', 1)), self::navigator());

        $shapes = [];
        foreach ($child->getSliceAccessors() as $accessor) {
            $shapes[$accessor['method']] = $accessor['single'];
        }
        // the override keeps returning a list like the accessor it overrides, a new accessor may return a single value.
        $this->assertSame(['getMrnIdentifier' => false, 'getSsnIdentifier' => true], $shapes);
    }
}
