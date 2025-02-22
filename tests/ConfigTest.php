<?php

namespace DCarbone\PHPFHIRTests;

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Version;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class ConfigTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ConfigTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        if (isset($this->_tmpdir)) {
            $this->_tmpdir->delete();
        }
    }

    protected function validBaseValues(string $funcName): array
    {
        return [
            'libPath' => $this->_tmpdir->path('output/src'),
            'libNsPrefix' => $funcName,
            'testsPath' => $this->_tmpdir->path('output/tests'),
            'testNsPrefix' => 'Tests' . PHPFHIR_NAMESPACE_SEPARATOR . $funcName,
        ];
    }

    protected function baseAssertions(Config $c,
                                      string $libPath,
                                      string $libNsPrefix,
                                      string $testsPath,
                                      string $testNsPrefix): void
    {
        $this->assertEquals($libPath, $c->getLibraryPath());
        $this->assertEquals($libNsPrefix, $c->getLibraryNamespacePrefix());
        $this->assertEquals($testsPath, $c->getTestsPath());
        $this->assertEquals($testNsPrefix, $c->getTestsNamespacePrefix());
        $this->assertEquals(
            LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL,
            $c->getLibrarySchemaLibxmlOpts(),
        );
    }

    public function testCanConstructWithEmptyVersionConfig(): void
    {
        extract($this->validBaseValues(__FUNCTION__));
        $c = new Config(
            libraryPath: $libPath,
            versions: [],
            libraryNamespacePrefix: $libNsPrefix,
            testsPath: $testsPath,
            testNamespacePrefix: $testNsPrefix,
        );
        $this->baseAssertions($c, $libPath, $libNsPrefix, $testsPath, $testNsPrefix);
        $this->assertEmpty($c->getVersionNames());
        $this->assertEmpty($c->getVersionsIterator());
    }

    public function testCanConstructWithValidMapVersionConfig(): void
    {
        extract($this->validBaseValues(__FUNCTION__));
        $schemaPath = $this->_tmpdir->path('input/Mock');
        $c = new Config(
            libraryPath: $libPath,
            versions: [
                [
                    'name' => 'Mock',
                    'schemaPath' => $schemaPath,
                    'defaultConfig' => [
                        'unserializeConfig' => [],
                        'serializeConfig' => [],
                    ],
                ],
            ],
            libraryNamespacePrefix: $libNsPrefix,
            testsPath: $testsPath,
            testNamespacePrefix: $testNsPrefix,
        );
        $this->baseAssertions($c, $libPath, $libNsPrefix, $testsPath, $testNsPrefix);
        $this->assertEquals(['Mock'], $c->getVersionNames());
        $this->assertCount(1, $c->getVersionsIterator());
        $this->assertInstanceOf(Version::class, $c->getVersion('Mock'));
    }

    public function testCanConstructWithValidVersionConfigClass()
    {
        extract($this->validBaseValues(__FUNCTION__));
        $schemaPath = $this->_tmpdir->path('input/Mock');
        $c = new Config(
            libraryPath: $libPath,
            versions: [
                new Config\VersionConfig(
                    name: 'Mock',
                    schemaPath: $schemaPath,
                    defaultConfig: [
                        'unserializeConfig' => [],
                        'serializeConfig' => [],
                    ],
                ),
            ],
            libraryNamespacePrefix: $libNsPrefix,
            testsPath: $testsPath,
            testNamespacePrefix: $testNsPrefix,
        );
        $this->baseAssertions($c, $libPath, $libNsPrefix, $testsPath, $testNsPrefix);
        $this->assertEquals(['Mock'], $c->getVersionNames());
        $this->assertCount(1, $c->getVersionsIterator());
        $this->assertInstanceOf(Version::class, $c->getVersion('Mock'));
    }
}
