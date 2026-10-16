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
use DCarbone\PHPFHIR\Profiles\Profile;
use DCarbone\PHPFHIR\Profiles\ProfileClass;
use DCarbone\PHPFHIR\Profiles\ProfileProperty;
use DCarbone\PHPFHIR\Profiles\ProfileTypeNavigator;
use DCarbone\PHPFHIR\Profiles\ProfileWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

/**
 * Renders a profile class whose validation code is generated against fake typed classes, then runs it on hydrated
 * objects: nothing is serialized along the way.
 */
class ProfileValidationTest extends TestCase
{
    use PackageFixtures;

    private const _NS = 'Acme\\Val\\R4\\Profiles';
    private const _T = 'Acme\\Val\\R4\\Types\\';
    private const _CLASS = self::_NS . '\\Ig\\Sample';

    private static TemporaryDirectory $_tmpdir;
    private static bool $_loaded = false;

    private static function prop(string $field, bool $collection, string $typeKey, ProfilePropertyKindEnum $kind = ProfilePropertyKindEnum::COMPLEX, null|string $class = null): ProfileProperty
    {
        return new ProfileProperty($field, 'get' . ucfirst($field), $collection, $typeKey, $class ?? self::_T . 'FHIR' . ucfirst($typeKey), $kind);
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
            public function __construct(private array $types, private array $choices)
            {
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

    /** Fake generated types: real getters over plain data. */
    private static function defineTypes(): void
    {
        $ns = 'namespace Acme\\Val\\R4\\Types;';
        $getters = static function (array $collections, array $singles): string {
            $out = '';
            foreach ($collections as $f) {
                $out .= sprintf('public function get%s(): array { return $this->d[%s] ?? []; }', ucfirst($f), var_export($f, true));
            }
            foreach ($singles as $f) {
                $out .= sprintf('public function get%s(): null|object { return $this->d[%s] ?? null; }', ucfirst($f), var_export($f, true));
            }
            return $out;
        };
        $base = 'public function __construct(public array $d = []) {}';

        eval($ns . 'class Prim { public function __construct(private mixed $v) {} public function getValue(): mixed { return $this->v; } }');
        eval($ns . 'class Box { public function __construct(private mixed $v) {} public function getValue(): null|Prim { return null === $this->v ? null : new Prim($this->v); } }');
        foreach (['Code', 'String', 'Uri', 'Boolean', 'DateTime', 'Integer'] as $n) {
            eval($ns . 'class FHIR' . $n . ' extends Box {}');
        }
        eval($ns . 'class FHIRAttachment {}');
        eval($ns . 'class FHIRHumanName { ' . $base . $getters([], ['family']) . ' }');
        eval($ns . 'class FHIRCoding { ' . $base . $getters([], ['system', 'code']) . ' }');
        eval($ns . 'class FHIRCodeableConcept { ' . $base . $getters(['coding'], []) . ' }');
        eval($ns . 'class FHIRPatientContact { ' . $base . $getters(['relationship'], []) . ' }');
        eval($ns . 'class FHIRIdentifier { ' . $base . $getters([], ['system']) . ' }');
        eval($ns . 'class FHIRExtension { ' . $base . 'public function getUrl(): null|Prim { return isset($this->d["url"]) ? new Prim($this->d["url"]) : null; } }');
        eval($ns . 'class FHIRPatient { ' . $base
            . $getters(['name', 'photo', 'contact', 'extension', 'identifier'], ['language', 'gender', 'maritalStatus', 'deceasedBoolean', 'deceasedDateTime', 'deceasedInteger'])
            . 'public function _getCombinedValidationRules(): array { return []; }'
            . 'public function _getValidationErrors(): array { return []; } }');
    }

    public static function setUpBeforeClass(): void
    {
        self::$_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfileValidationTest');
        self::$_tmpdir->create();
        if (self::$_loaded) {
            return;
        }
        self::defineTypes();

        $profile = Profile::fromStructureDefinition(self::structureDefinition('sample', 'Patient'));
        $compiled = [
            'rules' => [
                ['path' => 'name', 'min' => 1],
                ['path' => 'name.family', 'min' => 1, 'max' => 1],
                ['path' => 'photo', 'max' => 0],
                ['path' => 'contact', 'max' => 2],
                ['path' => 'deceased[x]', 'max' => 1, 'types' => ['boolean', 'dateTime']],
                ['path' => 'language', 'fixed' => 'en'],
                ['path' => 'maritalStatus', 'pattern' => ['coding' => [['system' => 's', 'code' => 'M']]]],
                ['path' => 'gender', 'codes' => [['http://g', 'male'], ['http://g', 'female']]],
                ['path' => 'contact.relationship', 'codes' => [['http://r', 'C']]],
            ],
            'slices' => [
                ['path' => 'extension', 'name' => 'race', 'match' => ['url' => 'http://x/race'], 'max' => 1],
                ['path' => 'identifier', 'name' => 'ssn', 'match' => ['system' => 'http://ssa.gov'], 'min' => 1, 'max' => 2],
            ],
            'invariants' => [],
            'skipped' => [],
        ];
        $class = new ProfileClass($profile, self::_NS . '\\Ig', 'Sample', self::_T . 'FHIRPatient', null, self::_NS, $compiled, self::navigator());

        $classes = new class($class) implements \IteratorAggregate {
            public function __construct(private ProfileClass $c)
            {
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([$this->c->getProfile()->getUrl() => $this->c]);
            }
        };
        ProfileWriter::write(self::$_tmpdir->path(''), self::_NS, '/* c */', ['pkg' => $classes], CoreEnumStubs::define());
        require self::$_tmpdir->path('Acme/Val/R4/Profiles/Ig/Sample.php');
        self::$_loaded = true;
    }

    public static function tearDownAfterClass(): void
    {
        self::$_tmpdir->delete();
    }

    private static function box(string $type, mixed $v): object
    {
        $c = self::_T . 'FHIR' . $type;
        return new $c($v);
    }

    private static function obj(string $type, array $d): object
    {
        $c = self::_T . 'FHIR' . $type;
        return new $c($d);
    }

    private static function concept(string $system, string $code): object
    {
        return self::obj('CodeableConcept', ['coding' => [self::obj('Coding', ['system' => self::box('Uri', $system), 'code' => self::box('Code', $code)])]]);
    }

    private static function patient(array $d = []): object
    {
        $class = self::_CLASS;
        return new $class(array_merge([
            'name' => [self::obj('HumanName', ['family' => self::box('String', 'F')])],
            'identifier' => [self::obj('Identifier', ['system' => self::box('Uri', 'http://ssa.gov')])],
        ], $d));
    }

    /** @return string[] "path@rule" for every error */
    private static function errors(object $patient): array
    {
        $out = [];
        foreach ($patient->_getValidationErrors() as $path => $rules) {
            foreach ($rules as $rule => $message) {
                $out[] = $path . '@' . $rule;
            }
        }
        return $out;
    }

    public static function casesProvider(): array
    {
        $ext = static fn(string $url): object => self::obj('Extension', ['url' => $url]);
        // built lazily: the fake types do not exist when providers run
        $cases = [
            'conforming' => [static fn(): object => self::patient(), []],
            'empty' => [static fn(): object => self::patient(['name' => [], 'identifier' => []]), ['identifier:ssn@profile_slice_min', 'name@profile_min']],
            'nested min' => [static fn(): object => self::patient(['name' => [self::obj('HumanName', [])]]), ['name[0].family@profile_min']],
            'prohibited' => [static fn(): object => self::patient(['photo' => [self::obj('Attachment', [])]]), ['photo@profile_max']],
            'choice allowed' => [static fn(): object => self::patient(['deceasedBoolean' => self::box('Boolean', true)]), []],
            'choice other allowed' => [static fn(): object => self::patient(['deceasedDateTime' => self::box('DateTime', '2020-01-01')]), []],
            'choice disallowed type' => [static fn(): object => self::patient(['deceasedInteger' => self::box('Integer', 3)]), ['deceasedInteger@profile_type']],
            'choice max' => [static fn(): object => self::patient(['deceasedBoolean' => self::box('Boolean', true), 'deceasedDateTime' => self::box('DateTime', '2020')]), ['deceased[x]@profile_max']],
            'fixed ok' => [static fn(): object => self::patient(['language' => self::box('Code', 'en')]), []],
            'fixed bad' => [static fn(): object => self::patient(['language' => self::box('Code', 'fr')]), ['language@profile_fixed']],
            'pattern ok' => [static fn(): object => self::patient(['maritalStatus' => self::concept('s', 'M')]), []],
            'pattern bad' => [static fn(): object => self::patient(['maritalStatus' => self::concept('s', 'U')]), ['maritalStatus@profile_pattern']],
            'code binding ok' => [static fn(): object => self::patient(['gender' => self::box('Code', 'male')]), []],
            'code binding bad' => [static fn(): object => self::patient(['gender' => self::box('Code', 'other')]), ['gender@profile_binding']],
            'concept binding ok' => [static fn(): object => self::patient(['contact' => [self::obj('PatientContact', ['relationship' => [self::concept('http://r', 'C')]])]]), []],
            'concept binding bad system' => [static fn(): object => self::patient(['contact' => [self::obj('PatientContact', ['relationship' => [self::concept('http://other', 'C')]])]]), ['contact[0].relationship[0]@profile_binding']],
            'concept binding bad code' => [static fn(): object => self::patient(['contact' => [self::obj('PatientContact', ['relationship' => [self::concept('http://r', 'Z')]])]]), ['contact[0].relationship[0]@profile_binding']],
            'slice max' => [static fn(): object => self::patient(['extension' => [$ext('http://x/race'), $ext('http://x/race'), $ext('http://other')]]), ['extension:race@profile_slice_max']],
        ];
        return $cases;
    }

    #[DataProvider('casesProvider')]
    public function testValidate(\Closure $patient, array $expected): void
    {
        $this->assertSame($expected, self::errors($patient()));
    }

    public function testTopLevelCardinalityDelegatedToTypeRules(): void
    {
        $this->assertSame(['contact' => ['max_occurs' => 2]], self::patient()->_getCombinedValidationRules());
    }

    public function testSliceAccessors(): void
    {
        $r = self::patient(['extension' => [self::obj('Extension', ['url' => 'http://other']), $mine = self::obj('Extension', ['url' => 'http://x/race'])]]);
        $this->assertSame($mine, $r->getRaceExtension());
        $this->assertNull(self::patient()->getRaceExtension());

        $a = self::obj('Identifier', ['system' => self::box('Uri', 'http://ssa.gov')]);
        $b = self::obj('Identifier', ['system' => self::box('Uri', 'other')]);
        $c = self::obj('Identifier', ['system' => self::box('Uri', 'http://ssa.gov')]);
        $this->assertSame([$a, $c], self::patient(['identifier' => [$a, $b, $c]])->getSsnIdentifier());
    }

    public function testMetadataConstants(): void
    {
        $class = self::_CLASS;
        $this->assertSame('http://example.org/fhir/StructureDefinition/sample', $class::PROFILE_URL);
        $this->assertSame([], $class::PROFILE_INVARIANTS);
    }
}
