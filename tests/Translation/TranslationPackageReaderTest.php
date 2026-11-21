<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Translation;

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

use DCarbone\PHPFHIR\Config\TranslationPackageConfig;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Translation\CodeMapDefinition;
use DCarbone\PHPFHIR\Translation\TranslationPackageReader;
use DCarbone\PHPFHIRTests\Profiles\PackageFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class TranslationPackageReaderTest extends TestCase
{
    use PackageFixtures;

    private TemporaryDirectory $_tmpdir;
    private string $_root;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'TranslationPackageReaderTest');
        $this->_tmpdir->create();
        $this->_root = rtrim($this->_tmpdir->path(''), '/');
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    public function testReadsEquivalentMappingsAndRetainsAmbiguousMappingsForDirectoryAndArchivePackages(): void
    {
        $conceptMap = [
            'resourceType' => 'ConceptMap',
            'id' => 'example',
            'group' => [
                [
                    'source' => 'http://example.org/source',
                    'target' => 'http://example.org/target',
                    'element' => [
                        ['code' => 'legacy', 'target' => [['code' => 'current', 'equivalence' => 'equivalent']]],
                        ['code' => 'exact', 'target' => [['code' => 'exact-target', 'equivalence' => 'equal']]],
                        ['code' => 'conditional', 'target' => [[
                            'code' => 'conditional-target',
                            'equivalence' => 'equivalent',
                            'dependsOn' => [['property' => 'context', 'value' => 'required']],
                        ]]],
                        ['code' => 'product', 'target' => [[
                            'code' => 'product-target',
                            'relationship' => 'equivalent',
                            'product' => [['attribute' => 'context', 'valueCode' => 'required']],
                        ]]],
                        [
                            'code' => 'ambiguous',
                            'target' => [
                                ['code' => 'one', 'relationship' => 'equivalent'],
                                ['code' => 'two', 'relationship' => 'equivalent'],
                            ],
                        ],
                        ['code' => 'related', 'target' => [['code' => 'related', 'relationship' => 'related-to']]],
                    ],
                ],
            ],
        ];
        $packageFiles = [
            'package.json' => self::manifest('example.xver'),
            'ConceptMap-example.json' => $conceptMap,
        ];
        $sources = [
            self::writePackageDir($this->_root . '/directory', $packageFiles),
            self::writePackageArchive($this->_root . '/archive.tgz', $packageFiles),
        ];

        foreach ($sources as $source) {
            $config = TranslationPackageConfig::fromArray([
                'sourceVersion' => 'R4',
                'targetVersion' => 'R5',
                'source' => $source,
            ]);
            $codeMaps = (new TranslationPackageReader())->readCodeMaps($config);

            $this->assertCount(1, $codeMaps);
            $this->assertSame('http://example.org/source', $codeMaps[0]->getSourceSystem());
            $this->assertSame('http://example.org/target', $codeMaps[0]->getTargetSystem());
            $this->assertSame([
                'ambiguous' => null,
                'conditional' => null,
                'exact' => 'exact-target',
                'legacy' => 'current',
                'product' => null,
                'related' => null,
            ], $codeMaps[0]->getCodeMappings());
        }
    }

    public function testSameSourceCodeMappedDifferentlyByAnotherConceptMapIsAmbiguous(): void
    {
        $conceptMap = static fn(string $id, string $target): array => [
            'resourceType' => 'ConceptMap',
            'id' => $id,
            'group' => [[
                'source' => 'http://example.org/source',
                'target' => 'http://example.org/target',
                'element' => [
                    ['code' => '7', 'target' => [['code' => $target, 'relationship' => 'equivalent']]],
                    ['code' => 'same', 'target' => [['code' => 'stable', 'relationship' => 'equivalent']]],
                ],
            ]],
        ];
        $source = self::writePackageDir($this->_root . '/dir', [
            'package.json' => self::manifest('example.xver'),
            'ConceptMap-one.json' => $conceptMap('one', 'first'),
            'ConceptMap-two.json' => $conceptMap('two', 'second'),
        ]);

        $codeMaps = (new TranslationPackageReader())->readCodeMaps(TranslationPackageConfig::fromArray([
            'sourceVersion' => 'R4',
            'targetVersion' => 'R5',
            'source' => $source,
        ]));

        $this->assertCount(1, $codeMaps);
        $this->assertSame(['7' => null, 'same' => 'stable'], $codeMaps[0]->getCodeMappings());
    }

    public function testMalformedConceptMapIsRejected(): void
    {
        $source = self::writePackageDir($this->_root . '/dir', [
            'package.json' => self::manifest('example.xver'),
            'ConceptMap-bad.json' => ['resourceType' => 'ConceptMap', 'id' => 'bad', 'group' => 'nope'],
        ]);

        $this->expectException(ProfileDefinitionException::class);
        (new TranslationPackageReader())->readCodeMaps(TranslationPackageConfig::fromArray([
            'sourceVersion' => 'R4',
            'targetVersion' => 'R5',
            'source' => $source,
        ]));
    }

    public function testGroupsForDifferentCodeSystemVersionsRemainDistinct(): void
    {
        $group = static fn(string $sourceVersion, string $targetVersion, string $targetCode): array => [
            'source' => 'http://example.org/source',
            'sourceVersion' => $sourceVersion,
            'target' => 'http://example.org/target',
            'targetVersion' => $targetVersion,
            'element' => [['code' => 'legacy', 'target' => [
                ['code' => $targetCode, 'equivalence' => 'equivalent'],
            ]]],
        ];
        $source = self::writePackageDir($this->_root . '/versions', [
            'package.json' => self::manifest('example.versions'),
            'ConceptMap-versions.json' => [
                'resourceType' => 'ConceptMap',
                'id' => 'versions',
                'group' => [
                    $group('1', '2', 'first'),
                    $group('1', '3', 'second'),
                    $group('2', '3', 'third'),
                ],
            ],
        ]);

        $maps = (new TranslationPackageReader())->readCodeMaps(new TranslationPackageConfig('R4', 'R5', $source));

        self::assertCount(3, $maps);
        self::assertSame(['1', '1', '2'], array_map(
            static fn(CodeMapDefinition $map): null|string => $map->getSourceVersion(),
            $maps,
        ));
        self::assertSame(['2', '3', '3'], array_map(
            static fn(CodeMapDefinition $map): null|string => $map->getTargetVersion(),
            $maps,
        ));
        self::assertSame(['first', 'second', 'third'], array_map(
            static fn(CodeMapDefinition $map): null|string => $map->getCodeMappings()['legacy'],
            $maps,
        ));
    }

    public function testMergingCodeMapsCombinesCodesAndMarksConflictsUnmappable(): void
    {
        $first = new CodeMapDefinition('http://example.org/source', 'http://example.org/target', [
            'conflict' => 'first',
            'same' => 'stable',
        ]);
        $second = new CodeMapDefinition('http://example.org/source', 'http://example.org/target', [
            'conflict' => 'second',
            'new' => 'added',
            'same' => 'stable',
        ]);

        self::assertSame([
            'conflict' => null,
            'new' => 'added',
            'same' => 'stable',
        ], $first->merge($second)->getCodeMappings());
    }

    /** @return array<string, array{null|string, null|string}> */
    public static function incompatibleVersionsProvider(): array
    {
        return [
            'different source version' => ['other', '2'],
            'different target version' => ['1', 'other'],
            'unspecified source version' => [null, '2'],
            'unspecified target version' => ['1', null],
        ];
    }

    #[DataProvider('incompatibleVersionsProvider')]
    public function testMergingDifferentCodeSystemVersionsIsRejected(
        null|string $sourceVersion,
        null|string $targetVersion,
    ): void {
        $first = new CodeMapDefinition('source', 'target', ['a' => 'b'], sourceVersion: '1', targetVersion: '2');
        $second = new CodeMapDefinition(
            'source',
            'target',
            ['c' => 'd'],
            sourceVersion: $sourceVersion,
            targetVersion: $targetVersion,
        );

        self::assertNotSame($first->getGroupKey(), $second->getGroupKey());
        $this->expectException(\InvalidArgumentException::class);
        $first->merge($second);
    }

    public function testMergingMatchingCodeSystemVersionsPreservesThem(): void
    {
        $first = new CodeMapDefinition('source', 'target', ['a' => 'b'], sourceVersion: '1', targetVersion: '2');
        $second = new CodeMapDefinition('source', 'target', ['c' => 'd'], sourceVersion: '1', targetVersion: '2');

        $merged = $first->merge($second);

        self::assertSame($first->getGroupKey(), $merged->getGroupKey());
        self::assertSame('1', $merged->getSourceVersion());
        self::assertSame('2', $merged->getTargetVersion());
        self::assertSame(['a' => 'b', 'c' => 'd'], $merged->getCodeMappings());
    }
}
