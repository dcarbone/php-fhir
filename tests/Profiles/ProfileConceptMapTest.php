<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Profiles;

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Profiles\ProfileConceptMap;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProfileConceptMapTest extends TestCase
{
    public function testReadsEquivalentTargetsOfEachElement(): void
    {
        $map = ProfileConceptMap::fromResource([
            'resourceType' => 'ConceptMap',
            'id' => 'cm',
            'group' => [
                [
                    'source' => 'http://example.org/source',
                    'target' => 'http://example.org/target',
                    'element' => [
                        // R4 name of the relationship, a repeated target, and a numeric target code.
                        ['code' => 'a', 'target' => [
                            ['code' => 'x', 'equivalence' => 'equivalent'],
                            ['code' => 'x', 'equivalence' => 'equivalent'],
                        ]],
                        ['code' => 'b', 'target' => [
                            ['code' => '1', 'relationship' => 'equivalent'],
                            ['code' => 'y', 'relationship' => 'equivalent'],
                        ]],
                        ['code' => 'c', 'target' => [['code' => 'z', 'relationship' => 'related-to']]],
                        ['code' => 'd'],
                    ],
                ],
            ],
        ]);

        $this->assertSame('cm', $map->id);
        $this->assertCount(1, $map->groups);
        $group = $map->groups[0];
        $this->assertSame('http://example.org/source', $group->source);
        $this->assertSame('http://example.org/target', $group->target);
        $this->assertSame(['a', 'b', 'c', 'd'], array_map(static fn($e) => $e->code, $group->elements));
        $this->assertSame(['x'], $group->elements[0]->equivalentTargets);
        $this->assertSame(['1', 'y'], $group->elements[1]->equivalentTargets);
        $this->assertSame([], $group->elements[2]->equivalentTargets);
        $this->assertSame([], $group->elements[3]->equivalentTargets);
    }

    public function testSkipsGroupsThatDoNotMapOneCodeSystemToAnother(): void
    {
        $map = ProfileConceptMap::fromResource([
            'id' => 'cm',
            'group' => [
                'not-an-array',
                ['source' => 'http://example.org/source', 'element' => [['code' => 'a']]],
                ['source' => '', 'target' => 'http://example.org/target'],
                ['source' => 'http://example.org/source', 'target' => 7],
            ],
        ]);
        $this->assertSame([], $map->groups);
    }

    /**
     * @return array<string, array{0: array, 1: string}>
     */
    public static function malformedProvider(): array
    {
        $group = static fn(array $overrides): array => [
            'id' => 'cm',
            'group' => [array_merge(['source' => 's', 'target' => 't'], $overrides)],
        ];
        return [
            'group not a list' => [['id' => 'cm', 'group' => 'nope'], 'has a non-array "group" value'],
            'element not a list' => [$group(['element' => 'nope']), 'has a non-array group "element" value'],
            'element without code' => [$group(['element' => [['target' => []]]]), 'without a string source code'],
            'element code not a string' => [$group(['element' => [['code' => 1]]]), 'without a string source code'],
            'target not a list' => [$group(['element' => [['code' => 'a', 'target' => 'nope']]]), 'non-array target list'],
        ];
    }

    #[DataProvider('malformedProvider')]
    public function testMalformedConceptMapsAreRejected(array $resource, string $message): void
    {
        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage($message);
        ProfileConceptMap::fromResource($resource);
    }
}
