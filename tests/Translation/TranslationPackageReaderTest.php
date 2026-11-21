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
use DCarbone\PHPFHIR\Translation\CodeMapDefinition;
use DCarbone\PHPFHIR\Translation\TranslationPackageReader;
use DCarbone\PHPFHIRTests\Profiles\PackageFixtures;
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
                'legacy' => 'current',
                'related' => null,
            ], $codeMaps[0]->getCodeMappings());
        }
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
}
