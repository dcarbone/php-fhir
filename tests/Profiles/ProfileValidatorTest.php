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
use DCarbone\PHPFHIR\Profiles\ProfileClass;
use DCarbone\PHPFHIR\Profiles\Profile;
use DCarbone\PHPFHIR\Profiles\ProfileWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

/**
 * Exercises the generated validator, and the accessors / validate() of a generated profile class, by rendering
 * them from the templates and loading the result.
 */
class ProfileValidatorTest extends TestCase
{
    use PackageFixtures;

    private const _NS = 'Acme\\Val\\R4\\Profiles';
    private const _VALIDATOR = self::_NS . '\\ProfileValidator';
    private const _CLASS = self::_NS . '\\Ig\\Sample';

    private static TemporaryDirectory $_tmpdir;
    private static bool $_loaded = false;

    public static function setUpBeforeClass(): void
    {
        self::$_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ProfileValidatorTest');
        self::$_tmpdir->create();
        if (self::$_loaded) {
            return;
        }

        // stand-in for a generated type: json serializable, with a repeating "extension" and "identifier".
        eval('namespace Acme\\Val\\R4\\Types; class FHIRPatient implements \\JsonSerializable {
            public function __construct(public array $data = []) {}
            public function jsonSerialize(): mixed { return $this->data; }
            public function getExtension(): array { return array_map(fn($e) => (object)$e, $this->data["extension"] ?? []); }
            public function getIdentifier(): array { return array_map(fn($e) => (object)$e, $this->data["identifier"] ?? []); }
        }');

        $profile = Profile::fromStructureDefinition(self::structureDefinition('sample', 'Patient'));
        $compiled = [
            'rules' => [
                ['path' => 'name', 'min' => 1],
                ['path' => 'name.family', 'min' => 1, 'max' => 1],
                ['path' => 'photo', 'max' => 0],
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
        $class = new ProfileClass($profile, self::_NS . '\\Ig', 'Sample', 'Acme\\Val\\R4\\Types\\FHIRPatient', null, self::_NS, $compiled);

        $classes = new class($class) implements \IteratorAggregate {
            public function __construct(private ProfileClass $c) {}
            public function getIterator(): \Traversable { return new \ArrayIterator([$this->c->getProfile()->getUrl() => $this->c]); }
        };
        ProfileWriter::write(self::$_tmpdir->path(''), self::_NS, '/* c */', ['pkg' => $classes]);
        foreach (['ProfileViolation', 'ProfileValidator'] as $name) {
            require self::$_tmpdir->path('Acme/Val/R4/Profiles/' . $name . '.php');
        }
        require self::$_tmpdir->path('Acme/Val/R4/Profiles/Ig/Sample.php');
        self::$_loaded = true;
    }

    public static function tearDownAfterClass(): void
    {
        self::$_tmpdir->delete();
    }

    private static function good(array $overrides = []): array
    {
        return array_merge([
            'resourceType' => 'Patient',
            'name' => [['family' => 'F']],
            'identifier' => [['system' => 'http://ssa.gov', 'value' => '1']],
        ], $overrides);
    }

    /** @return string[] "rule@path" for every violation */
    private static function violations(array $data): array
    {
        $v = self::_VALIDATOR;
        $class = self::_CLASS;
        return array_map(
            static fn($violation): string => $violation->getRule() . '@' . $violation->getPath(),
            (new $class($data))->validate(),
        );
    }

    public static function casesProvider(): array
    {
        return [
            'conforming' => [self::good(), []],
            'empty' => [[], ['min@name', 'slice-min@identifier:ssn']],
            'name via primitive extension only counts as present' => [self::good(['name' => null, '_name' => [['id' => 'a']]]), ['min@name[0].family']],
            'nested min only applies when parent exists' => [self::good(['name' => [['family' => 'F'], ['given' => ['x']]]]), ['min@name[1].family']],
            'nested max' => [self::good(['name' => [['family' => ['a', 'b']]]]), ['max@name[0].family']],
            'prohibited' => [self::good(['photo' => [['url' => 'x']]]), ['max@photo']],
            'choice allowed type' => [self::good(['deceasedBoolean' => true]), []],
            'choice other allowed type' => [self::good(['deceasedDateTime' => '2020-01-01']), []],
            'choice disallowed type' => [self::good(['deceasedInteger' => 3]), ['type@deceasedInteger']],
            'choice max' => [self::good(['deceasedBoolean' => true, 'deceasedDateTime' => '2020']), ['max@deceased[x]']],
            'fixed ok' => [self::good(['language' => 'en']), []],
            'fixed bad' => [self::good(['language' => 'fr']), ['fixed@language']],
            'pattern ok with extras' => [self::good(['maritalStatus' => ['coding' => [['system' => 'x', 'code' => 'Z'], ['system' => 's', 'code' => 'M', 'display' => 'd']]]]), []],
            'pattern bad' => [self::good(['maritalStatus' => ['coding' => [['system' => 's', 'code' => 'U']]]]), ['pattern@maritalStatus']],
            'code binding ok' => [self::good(['gender' => 'male']), []],
            'code binding bad' => [self::good(['gender' => 'other']), ['binding@gender']],
            'codeable concept binding' => [self::good(['contact' => [['relationship' => [['coding' => [['system' => 'http://r', 'code' => 'C']]]]]]]), []],
            'codeable concept binding bad system' => [self::good(['contact' => [['relationship' => [['coding' => [['system' => 'http://other', 'code' => 'C']]]]]]]), ['binding@contact[0].relationship[0]']],
            'codeable concept binding bad' => [self::good(['contact' => [['relationship' => [['coding' => [['system' => 'http://r', 'code' => 'Z']]]]]]]), ['binding@contact[0].relationship[0]']],
            'slice max' => [self::good(['extension' => [['url' => 'http://x/race'], ['url' => 'http://x/race'], ['url' => 'http://other']]]), ['slice-max@extension:race']],
            'slice min' => [self::good(['identifier' => [['system' => 'other']]]), ['slice-min@identifier:ssn']],
            'slice counted across singleton' => [self::good(['identifier' => ['system' => 'http://ssa.gov']]), []],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testValidate(array $data, array $expected): void
    {
        $this->assertSame($expected, self::violations($data));
    }

    public function testIsValidAndMessages(): void
    {
        $class = self::_CLASS;
        $this->assertTrue((new $class(self::good()))->isValid());
        $bad = new $class([]);
        $this->assertFalse($bad->isValid());
        $this->assertSame('name: requires at least 1, found 0', (string)$bad->validate()[0]);
        $this->assertSame(
            ['path' => 'name', 'rule' => 'min', 'message' => 'requires at least 1, found 0'],
            $bad->validate()[0]->jsonSerialize(),
        );
    }

    public function testValidateAcceptsEncodableNonObjects(): void
    {
        $v = self::_VALIDATOR;
        $this->assertSame([], $v::validate('scalar', [['path' => 'name', 'min' => 1]], []));
        $this->assertCount(1, $v::validate(new \ArrayObject([]), [['path' => 'name', 'min' => 1]], []));
    }

    public function testSliceAccessors(): void
    {
        $class = self::_CLASS;
        $r = new $class(self::good(['extension' => [['url' => 'http://other'], ['url' => 'http://x/race', 'id' => 'r']]]));
        $this->assertTrue(method_exists($r, 'getRaceExtension'));
        $this->assertSame('r', $r->getRaceExtension()->id);
        $this->assertNull((new $class(self::good()))->getRaceExtension());

        $two = new $class(self::good(['identifier' => [['system' => 'http://ssa.gov', 'value' => '1'], ['system' => 'o'], ['system' => 'http://ssa.gov', 'value' => '2']]]));
        $found = $two->getSsnIdentifier();
        $this->assertCount(2, $found);
        $this->assertSame(['1', '2'], array_map(fn($i) => $i->value, $found));
    }

    public function testMetadataConstants(): void
    {
        $class = self::_CLASS;
        $this->assertCount(8, $class::PROFILE_RULES);
        $this->assertSame('http://example.org/fhir/StructureDefinition/sample', $class::PROFILE_URL);
        $this->assertSame([], $class::PROFILE_INVARIANTS);
    }
}
