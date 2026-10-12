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

    public function testCyclicValueSetsTerminate(): void
    {
        $e = $this->expander([
            self::vs('a', ['compose' => ['include' => [['valueSet' => [self::_VS . 'b']]]]]),
            self::vs('b', ['compose' => ['include' => [['valueSet' => [self::_VS . 'a']]]]]),
        ]);
        $this->assertNull($e->expand(self::_VS . 'a'));
    }
}
