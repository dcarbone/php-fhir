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

use DCarbone\PHPFHIR\Config\ProfilePackageConfig;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use DCarbone\PHPFHIR\Profiles\ValueSetExpander;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ValueSetExpanderTest extends TestCase
{
    use PackageFixtures;

    private const _VS = 'http://example.org/fhir/ValueSet/';
    private const _CS = 'http://example.org/fhir/CodeSystem/';

    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ValueSetExpanderTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function expander(array $resources): ValueSetExpander
    {
        $files = ['package.json' => self::manifest('test.ig')];
        foreach ($resources as $i => $r) {
            $files[$r['resourceType'] . '-' . $i . '.json'] = $r;
        }
        $path = rtrim($this->_tmpdir->path(''), '/') . '/p-' . uniqid();
        self::writePackageDir($path, $files);
        return new ValueSetExpander(ProfilePackageReader::read(
            ProfilePackageConfig::fromArray(['name' => 'test.ig', 'source' => $path]),
        ));
    }

    private static function vs(string $id, array $extra): array
    {
        return array_merge(self::valueSet($id), $extra);
    }

    public function testUnknownValueSetIsNotExpandable(): void
    {
        $this->assertNull($this->expander([])->expand(self::_VS . 'nope'));
    }

    public function testExplicitConcepts(): void
    {
        $e = $this->expander([self::vs('a', ['compose' => ['include' => [
            ['system' => 's1', 'concept' => [['code' => 'x'], ['code' => 'y']]],
            ['system' => 's2', 'concept' => [['code' => 'x']]],
        ]]])]);
        $this->assertSame([['s1', 'x'], ['s1', 'y'], ['s2', 'x']], $e->expand(self::_VS . 'a'));
        $this->assertSame([['s1', 'x'], ['s1', 'y'], ['s2', 'x']], $e->expand(self::_VS . 'a|1.0'));
    }

    public function testWholeCodeSystemIncludesNestedConcepts(): void
    {
        $cs = array_merge(self::codeSystem('cs'), ['concept' => [
            ['code' => 'p', 'concept' => [['code' => 'c1'], ['code' => 'c2']]],
        ]]);
        $e = $this->expander([$cs, self::vs('a', ['compose' => ['include' => [['system' => self::_CS . 'cs']]]])]);
        $this->assertSame([[self::_CS . 'cs', 'p'], [self::_CS . 'cs', 'c1'], [self::_CS . 'cs', 'c2']], $e->expand(self::_VS . 'a'));
    }

    public function testCodeSystemNotInPackageIsNotExpandable(): void
    {
        $e = $this->expander([self::vs('a', ['compose' => ['include' => [['system' => 'http://snomed.info/sct']]]])]);
        $this->assertNull($e->expand(self::_VS . 'a'));
    }

    public function testIncompleteCodeSystemIsNotExpandable(): void
    {
        $cs = array_merge(self::codeSystem('cs'), ['content' => 'fragment', 'concept' => [['code' => 'x']]]);
        $e = $this->expander([$cs, self::vs('a', ['compose' => ['include' => [['system' => self::_CS . 'cs']]]])]);
        $this->assertNull($e->expand(self::_VS . 'a'));
    }

    public function testFiltersAreNotExpandable(): void
    {
        $e = $this->expander([self::vs('a', ['compose' => ['include' => [
            ['system' => 's', 'filter' => [['property' => 'p', 'op' => '=', 'value' => 'v']]],
        ]]])]);
        $this->assertNull($e->expand(self::_VS . 'a'));
    }

    public function testExpansionTakesPrecedenceAndSkipsAbstract(): void
    {
        $e = $this->expander([self::vs('a', ['expansion' => ['contains' => [
            ['system' => 's', 'code' => 'a', 'contains' => [['system' => 's', 'code' => 'b']]],
            ['system' => 's', 'code' => 'grp', 'abstract' => true],
        ]]])]);
        $this->assertSame([['s', 'a'], ['s', 'b']], $e->expand(self::_VS . 'a'));
    }

    public function testCompleteExpansionWithTotalIsUsed(): void
    {
        $e = $this->expander([self::vs('a', ['expansion' => ['total' => 2, 'offset' => 0, 'contains' => [
            ['system' => 's', 'code' => 'a'],
            ['system' => 's', 'code' => 'b'],
        ]]])]);
        $this->assertSame([['s', 'a'], ['s', 'b']], $e->expand(self::_VS . 'a'));
    }

    public function testPagedExpansionFallsBackToCompose(): void
    {
        $compose = ['include' => [['system' => 's', 'concept' => [['code' => 'a'], ['code' => 'b'], ['code' => 'c']]]]];
        $e = $this->expander([
            self::vs('total', ['compose' => $compose, 'expansion' => ['total' => 3, 'contains' => [
                ['system' => 's', 'code' => 'a'],
            ]]]),
            self::vs('offset', ['compose' => $compose, 'expansion' => ['offset' => 1, 'contains' => [
                ['system' => 's', 'code' => 'b'],
                ['system' => 's', 'code' => 'c'],
            ]]]),
        ]);
        $all = [['s', 'a'], ['s', 'b'], ['s', 'c']];
        $this->assertSame($all, $e->expand(self::_VS . 'total'));
        $this->assertSame($all, $e->expand(self::_VS . 'offset'));
    }

    public function testPagedExpansionWithoutComposeIsNotExpandable(): void
    {
        $e = $this->expander([
            self::vs('total', ['expansion' => ['total' => 3, 'contains' => [['system' => 's', 'code' => 'a']]]]),
            self::vs('offset', ['expansion' => ['offset' => 1, 'contains' => [['system' => 's', 'code' => 'b']]]]),
        ]);
        $this->assertNull($e->expand(self::_VS . 'total'));
        $this->assertNull($e->expand(self::_VS . 'offset'));
    }

    public function testNestedValueSetsAndExclude(): void
    {
        $e = $this->expander([
            self::vs('base', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => '1'], ['code' => '2'], ['code' => '3']]]]]]),
            self::vs('other', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => '2'], ['code' => '3'], ['code' => '4']]]]]]),
            self::vs('union', ['compose' => ['include' => [['valueSet' => [self::_VS . 'base']], ['valueSet' => [self::_VS . 'other']]]]]),
            self::vs('inter', ['compose' => ['include' => [['valueSet' => [self::_VS . 'base', self::_VS . 'other']]]]]),
            self::vs('minus', ['compose' => [
                'include' => [['valueSet' => [self::_VS . 'base']]],
                'exclude' => [['system' => 's', 'concept' => [['code' => '2']]]],
            ]]),
            self::vs('missing', ['compose' => ['include' => [['valueSet' => [self::_VS . 'nope']]]]]),
        ]);
        $this->assertSame([['s', '1'], ['s', '2'], ['s', '3'], ['s', '4']], $e->expand(self::_VS . 'union'));
        $this->assertSame([['s', '2'], ['s', '3']], $e->expand(self::_VS . 'inter'));
        $this->assertSame([['s', '1'], ['s', '3']], $e->expand(self::_VS . 'minus'));
        $this->assertNull($e->expand(self::_VS . 'missing'));
    }

    public function testAbstractAndNestedEntriesCountTowardsTotal(): void
    {
        $e = $this->expander([self::vs('a', ['expansion' => ['total' => 3, 'contains' => [
            ['system' => 's', 'code' => 'grp', 'abstract' => true, 'contains' => [['system' => 's', 'code' => 'b']]],
            ['system' => 's', 'code' => 'c'],
        ]]])]);
        $this->assertSame([['s', 'b'], ['s', 'c']], $e->expand(self::_VS . 'a'));
    }

    /**
     * Expansions that cannot be shown to hold every code: each falls back to compose when it has one, and is not
     * expandable otherwise.
     */
    public static function incompleteExpansionsProvider(): array
    {
        $a = ['system' => 's', 'code' => 'a'];
        $b = ['system' => 's', 'code' => 'b'];
        return [
            'nonzero offset' => [['offset' => 2, 'contains' => [$a, $b]]],
            'total above listed codes' => [['total' => 3, 'contains' => [$a, $b]]],
            'codeless group not counted' => [['total' => 2, 'contains' => [['display' => 'g', 'contains' => [$a]]]]],
            'repeated code not counted' => [['total' => 2, 'contains' => [$a, ['system' => 's', 'code' => 'a']]]],
            'same code, other system counted apart' => [['total' => 3, 'contains' => [$a, ['system' => 't', 'code' => 'a']]]],
            'non-integer total' => [['total' => '2', 'contains' => [$a, $b]]],
            'non-integer offset' => [['offset' => '0', 'contains' => [$a, $b]]],
            'offset parameter' => [['parameter' => [['name' => 'offset', 'valueInteger' => 2]], 'contains' => [$a, $b]]],
            'count parameter without total' => [['parameter' => [['name' => 'count', 'valueInteger' => 2]], 'contains' => [$a, $b]]],
            'count parameter, total above listed codes' => [['total' => 5, 'parameter' => [['name' => 'count', 'valueInteger' => 2]], 'contains' => [$a, $b]]],
            'too costly' => [['extension' => [['url' => 'http://hl7.org/fhir/StructureDefinition/valueset-toocostly', 'valueBoolean' => true]], 'contains' => [$a, $b]]],
            'unclosed' => [['extension' => [['url' => 'http://hl7.org/fhir/StructureDefinition/valueset-unclosed', 'valueBoolean' => true]], 'contains' => [$a, $b]]],
        ];
    }

    #[DataProvider('incompleteExpansionsProvider')]
    public function testIncompleteExpansionIsNotUsed(array $expansion): void
    {
        $compose = ['include' => [['system' => 's', 'concept' => [['code' => 'a'], ['code' => 'b'], ['code' => 'c']]]]];
        $e = $this->expander([
            self::vs('withCompose', ['expansion' => $expansion, 'compose' => $compose]),
            self::vs('withoutCompose', ['expansion' => $expansion]),
        ]);
        $this->assertSame([['s', 'a'], ['s', 'b'], ['s', 'c']], $e->expand(self::_VS . 'withCompose'));
        $this->assertNull($e->expand(self::_VS . 'withoutCompose'));
    }

    public static function completeExpansionsProvider(): array
    {
        $a = ['system' => 's', 'code' => 'a'];
        $b = ['system' => 's', 'code' => 'b'];
        return [
            'no paging information' => [['contains' => [$a, $b]]],
            'zero offset, total met' => [['offset' => 0, 'total' => 2, 'contains' => [$a, $b]]],
            'total below listed codes' => [['total' => 1, 'contains' => [$a, $b]]],
            'zero offset parameter' => [['parameter' => [['name' => 'offset', 'valueInteger' => 0]], 'contains' => [$a, $b]]],
            'count parameter, total met' => [['total' => 2, 'parameter' => [['name' => 'count', 'valueInteger' => 10]], 'contains' => [$a, $b]]],
            'unrelated parameters and extensions' => [[
                'parameter' => [['name' => 'excludeNotForUI', 'valueBoolean' => true]],
                'extension' => [['url' => 'http://hl7.org/fhir/StructureDefinition/valueset-expansionSource', 'valueUri' => 'x']],
                'contains' => [$a, $b],
            ]],
            'abstract code counted' => [['total' => 3, 'contains' => [['system' => 's', 'code' => 'g', 'abstract' => true], $a, $b]]],
        ];
    }

    #[DataProvider('completeExpansionsProvider')]
    public function testCompleteExpansionIsUsed(array $expansion): void
    {
        $compose = ['include' => [['system' => 's', 'concept' => [['code' => 'a'], ['code' => 'b'], ['code' => 'c']]]]];
        $e = $this->expander([self::vs('a', ['expansion' => $expansion, 'compose' => $compose])]);
        $this->assertSame([['s', 'a'], ['s', 'b']], $e->expand(self::_VS . 'a'));
    }

    public function testExpansionEntriesWithoutSystemOrCode(): void
    {
        $e = $this->expander([self::vs('a', ['expansion' => ['contains' => [
            ['code' => 'x'],
            ['display' => 'group', 'contains' => [['system' => 's', 'code' => 'y']]],
        ]]])]);
        $this->assertSame([[null, 'x'], ['s', 'y']], $e->expand(self::_VS . 'a'));
    }

    public function testExpansionDuplicatesAreRemoved(): void
    {
        $e = $this->expander([self::vs('a', ['expansion' => ['contains' => [
            ['system' => 's', 'code' => 'x', 'contains' => [['system' => 's', 'code' => 'x']]],
            ['system' => 't', 'code' => 'x'],
        ]]])]);
        $this->assertSame([['s', 'x'], ['t', 'x']], $e->expand(self::_VS . 'a'));
    }

    public function testComposeDuplicatesAreRemoved(): void
    {
        $e = $this->expander([self::vs('a', ['compose' => ['include' => [
            ['system' => 's', 'concept' => [['code' => 'x'], ['code' => 'y']]],
            ['system' => 's', 'concept' => [['code' => 'y'], ['code' => 'z']]],
        ]]])]);
        $this->assertSame([['s', 'x'], ['s', 'y'], ['s', 'z']], $e->expand(self::_VS . 'a'));
    }

    public function testPagedExpansionOfIncludedValueSet(): void
    {
        $paged = ['total' => 2, 'contains' => [['system' => 's', 'code' => 'a']]];
        $e = $this->expander([
            self::vs('pagedWithCompose', ['expansion' => $paged, 'compose' => ['include' => [
                ['system' => 's', 'concept' => [['code' => 'a'], ['code' => 'b']]],
            ]]]),
            self::vs('pagedOnly', ['expansion' => $paged]),
            self::vs('outer1', ['compose' => ['include' => [['valueSet' => [self::_VS . 'pagedWithCompose']]]]]),
            self::vs('outer2', ['compose' => ['include' => [['valueSet' => [self::_VS . 'pagedOnly']]]]]),
        ]);
        $this->assertSame([['s', 'a'], ['s', 'b']], $e->expand(self::_VS . 'outer1'));
        $this->assertNull($e->expand(self::_VS . 'outer2'));
    }

    public function testUnsupportedIncludesAreNotExpandable(): void
    {
        $e = $this->expander([
            self::vs('base', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'x']]]]]]),
            self::vs('empty', ['compose' => ['include' => [['version' => '1']]]]),
            self::vs('both', ['compose' => ['include' => [['system' => 's', 'valueSet' => [self::_VS . 'base']]]]]),
            self::vs('noCode', ['compose' => ['include' => [['system' => 's', 'concept' => [['display' => 'x']]]]]]),
            self::vs('badExclude', ['compose' => [
                'include' => [['valueSet' => [self::_VS . 'base']]],
                'exclude' => [['system' => 's', 'filter' => [['property' => 'p', 'op' => '=', 'value' => 'v']]]],
            ]]),
            self::vs('noCompose', []),
        ]);
        foreach (['empty', 'both', 'noCode', 'badExclude', 'noCompose'] as $id) {
            $this->assertNull($e->expand(self::_VS . $id), $id);
        }
    }

    public function testEmptyResultIsNotExpandable(): void
    {
        $e = $this->expander([
            self::vs('a', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'x']]]]]]),
            self::vs('b', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'y']]]]]]),
            self::vs('disjoint', ['compose' => ['include' => [['valueSet' => [self::_VS . 'a', self::_VS . 'b']]]]]),
            self::vs('excludedAll', ['compose' => [
                'include' => [['valueSet' => [self::_VS . 'a']]],
                'exclude' => [['system' => 's', 'concept' => [['code' => 'x']]]],
            ]]),
            self::vs('abstractOnly', ['expansion' => ['contains' => [['system' => 's', 'code' => 'g', 'abstract' => true]]]]),
        ]);
        foreach (['disjoint', 'excludedAll', 'abstractOnly'] as $id) {
            $this->assertNull($e->expand(self::_VS . $id), $id);
        }
    }

    public function testDeeplyNestedValueSetsAreExpandable(): void
    {
        $chain = function (string $prefix, int $length): array {
            $out = [];
            for ($i = 0; $i < $length - 1; $i++) {
                $out[] = self::vs($prefix . $i, ['compose' => ['include' => [['valueSet' => [self::_VS . $prefix . ($i + 1)]]]]]);
            }
            $out[] = self::vs($prefix . ($length - 1), ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'x']]]]]]);
            return $out;
        };
        $e = $this->expander($chain('deep', 25));
        $this->assertSame([['s', 'x']], $e->expand(self::_VS . 'deep0'));
        $this->assertSame([['s', 'x']], $e->expand(self::_VS . 'deep24'));
    }

    public function testCyclicValueSetsTerminate(): void
    {
        $e = $this->expander([
            self::vs('a', ['compose' => ['include' => [['valueSet' => [self::_VS . 'b']]]]]),
            self::vs('b', ['compose' => ['include' => [['valueSet' => [self::_VS . 'a']]]]]),
            self::vs('self', ['compose' => ['include' => [['valueSet' => [self::_VS . 'self']]]]]),
            // a cycle in one include does not poison an otherwise expandable sibling
            self::vs('c', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'x']]]]]]),
        ]);
        $this->assertNull($e->expand(self::_VS . 'a'));
        $this->assertNull($e->expand(self::_VS . 'b'));
        $this->assertNull($e->expand(self::_VS . 'self'));
        $this->assertSame([['s', 'x']], $e->expand(self::_VS . 'c'));
    }

    public function testExpansionsAreCached(): void
    {
        $e = $this->expander([
            self::vs('a', ['compose' => ['include' => [['system' => 's', 'concept' => [['code' => 'x']]]]]]),
            self::vs('b', ['compose' => ['include' => [['valueSet' => [self::_VS . 'a']]]]]),
        ]);
        $cache = new \ReflectionProperty($e, '_cache');
        $this->assertSame([], $cache->getValue($e));
        $this->assertSame([['s', 'x']], $e->expand(self::_VS . 'b|2.0'));
        // the included ValueSet is cached along the way, keyed without a version.
        $this->assertSame([self::_VS . 'a', self::_VS . 'b'], array_keys($cache->getValue($e)));
        $this->assertNull($e->expand(self::_VS . 'nope'));
        $this->assertSame([['s', 'x']], $e->expand(self::_VS . 'a'));
        $this->assertSame([self::_VS . 'a', self::_VS . 'b', self::_VS . 'nope'], array_keys($cache->getValue($e)));
    }
}
