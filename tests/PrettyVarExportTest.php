<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests;

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

use DCarbone\PHPFHIR\Render\PHPExpression;
use PHPUnit\Framework\TestCase;

class PrettyVarExportTest extends TestCase
{
    public function testScalars(): void
    {
        $this->assertSame("'s'", pretty_var_export('s'));
        $this->assertSame('NULL', pretty_var_export(null));
        $this->assertSame('[]', pretty_var_export([]));
    }

    public function testListKeysAreOmitted(): void
    {
        $this->assertSame(
            "[\n    'a' => [\n        1,\n        'x',\n    ],\n    'b' => NULL,\n]",
            pretty_var_export(['a' => [1, 'x'], 'b' => null]),
        );
    }

    public function testNonListIntKeysAreKept(): void
    {
        $this->assertSame("[\n    1 => 'a',\n    3 => 'b',\n]", pretty_var_export([1 => 'a', 3 => 'b']));
    }

    public function testIndent(): void
    {
        $this->assertSame("[\n        'a',\n    ]", pretty_var_export(['a'], 1));
        $this->assertSame("[\n  'k' => [\n    1,\n  ],\n]", pretty_var_export(['k' => [1]], 0, 2));
    }

    public function testExpressionsAreWrittenAsIs(): void
    {
        $this->assertSame('self::X', pretty_var_export(new PHPExpression('self::X')));
        $this->assertSame("[\n    'm' => self::X,\n]", pretty_var_export(['m' => new PHPExpression('self::X')]));
    }

    public function testRawKeys(): void
    {
        $this->assertSame(
            "[\n    self::FIELD_A => [\n        'k' => 1,\n    ],\n    'a.b' => [],\n]",
            pretty_var_export(['self::FIELD_A' => ['k' => 1], "'a.b'" => []], rawKeys: true),
        );
    }

    public function testOptionMaskKeys(): void
    {
        $this->assertSame("[\n    'libxmlOpts' => 1 | 2,\n]", pretty_var_export(['libxmlOptMask' => '1 | 2']));
    }
}
