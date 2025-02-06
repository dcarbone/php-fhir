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

    public function testCanConstructValidValues()
    {
        $libPath = $this->_tmpdir->path('input/src');
        $testPath = $this->_tmpdir->path('input/tests');
        $ns = 'PHPFHIRTests';
        $c = new Config(
            libraryPath: $libPath,
            versions: [],
            libraryNamespacePrefix: $ns,
            testsPath: $testPath
        );
        $this->assertEquals($libPath, $c->getLibraryPath());
        $this->assertEquals($ns, $c->getLibraryNamespacePrefix());
        $this->assertEquals(
            LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL,
            $c->getLibrarySchemaLibxmlOpts(),
        );
        $this->assertEmpty($c->getVersionNames());
        $this->assertEmpty($c->getVersionsIterator());
    }
}
