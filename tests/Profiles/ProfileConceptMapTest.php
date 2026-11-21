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

    public function testReadsCodeSystemVersionsFromR4FieldsAndR5Canonicals(): void
    {
        $map = ProfileConceptMap::fromResource([
            'id' => 'versions',
            'group' => [
                [
                    'source' => 'http://example.org/source',
                    'sourceVersion' => '1',
                    'target' => 'http://example.org/target',
                    'targetVersion' => '2',
                ],
                [
                    'source' => 'http://example.org/source|3',
                    'target' => 'http://example.org/target|4',
                ],
            ],
        ]);

        self::assertCount(2, $map->groups);
        foreach ($map->groups as $group) {
            self::assertSame('http://example.org/source', $group->source);
            self::assertSame('http://example.org/target', $group->target);
        }
        self::assertSame('1', $map->groups[0]->sourceVersion);
        self::assertSame('2', $map->groups[0]->targetVersion);
        self::assertSame('3', $map->groups[1]->sourceVersion);
        self::assertSame('4', $map->groups[1]->targetVersion);
    }

    /** @return array<string, array{list<array<string, mixed>>, list<string>}> */
    public static function usableTargetsProvider(): array
    {
        return [
            'R4 exact equality' => [[['code' => 'exact', 'equivalence' => 'equal']], ['exact']],
            'R5 does not define equal' => [[['code' => 'exact', 'relationship' => 'equal']], []],
            'R4 condition' => [[[
                'code' => 'conditional',
                'equivalence' => 'equivalent',
                'dependsOn' => [['property' => 'context', 'value' => 'required']],
            ]], []],
            'R5 required product' => [[[
                'code' => 'product',
                'relationship' => 'equivalent',
                'product' => [['attribute' => 'context', 'valueCode' => 'required']],
            ]], []],
            'unconditional alternative survives' => [[
                ['code' => 'unconditional', 'relationship' => 'equivalent'],
                [
                    'code' => 'conditional',
                    'relationship' => 'equivalent',
                    'dependsOn' => [['attribute' => 'context', 'valueCode' => 'required']],
                ],
            ], ['unconditional']],
            'empty conditions and products' => [[[
                'code' => 'unconditional',
                'equivalence' => 'equivalent',
                'dependsOn' => [],
                'product' => [],
            ]], ['unconditional']],
        ];
    }

    /**
     * @param list<array<string, mixed>> $targets
     * @param list<string> $expected
     */
    #[DataProvider('usableTargetsProvider')]
    public function testOnlyUnconditionalEquivalentOrExactTargetsAreUsable(array $targets, array $expected): void
    {
        $map = ProfileConceptMap::fromResource([
            'id' => 'target-details',
            'group' => [[
                'source' => 'http://example.org/source',
                'target' => 'http://example.org/target',
                'element' => [['code' => 'source', 'target' => $targets]],
            ]],
        ]);

        self::assertSame($expected, $map->groups[0]->elements[0]->equivalentTargets);
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
            'source version not a string' => [$group(['sourceVersion' => 1]), 'invalid "sourceVersion"'],
            'target version empty' => [$group(['targetVersion' => '']), 'invalid "targetVersion"'],
            'canonical version empty' => [$group(['source' => 'http://example.org/source|']), 'invalid "sourceVersion"'],
            'conflicting source versions' => [
                $group(['source' => 'http://example.org/source|1', 'sourceVersion' => '2']),
                'conflicting "sourceVersion"',
            ],
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
