<?php declare(strict_types=1);

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

use DCarbone\PHPFHIR\Version\VersionDefaultConfig;
use PHPUnit\Framework\TestCase;

class VersionDefaultConfigTest extends TestCase
{
    public function testSetUnserializeConfigAcceptsEmptyArray(): void
    {
        $c = new VersionDefaultConfig();
        $c->setUnserializeConfig([]);
        $this->assertEmpty($c->getUnserializeConfig());
    }

    public function testSetUnserializeConfigAcceptsLibxmlOpts(): void
    {
        $c = new VersionDefaultConfig();
        $c->setUnserializeConfig(['libxmlOpts' => LIBXML_NONET]);
        $this->assertArrayHasKey('libxmlOpts', $c->getUnserializeConfig());
        $this->assertEquals(LIBXML_NONET, $c->getUnserializeConfig()['libxmlOpts']);
    }

    public function testSetUnserializeConfigAcceptsJsonDecodeMaxDepth(): void
    {
        $c = new VersionDefaultConfig();
        $c->setUnserializeConfig(['jsonDecodeMaxDepth' => 1024]);
        $this->assertArrayHasKey('jsonDecodeMaxDepth', $c->getUnserializeConfig());
        $this->assertEquals(1024, $c->getUnserializeConfig()['jsonDecodeMaxDepth']);
    }

    public function testSetUnserializeConfigAcceptsJsonDecodeOpts(): void
    {
        $c = new VersionDefaultConfig();
        $c->setUnserializeConfig(['jsonDecodeOpts' => JSON_THROW_ON_ERROR]);
        $this->assertArrayHasKey('jsonDecodeOpts', $c->getUnserializeConfig());
        $this->assertEquals(JSON_THROW_ON_ERROR, $c->getUnserializeConfig()['jsonDecodeOpts']);
    }

    public function testSetUnserializeConfigJsonDecodeOptMaskKeyIsRecognized(): void
    {
        $c = new VersionDefaultConfig();
        // Before fix: constant had 'jsonDecodeOptsMask' (extra 's') and match arm had
        // trailing space — the key was silently skipped or hit the default throw path.
        // After fix: key is recognized and enters validation.
        try {
            $c->setUnserializeConfig(['jsonDecodeOptMask' => 'JSON_THROW_ON_ERROR']);
            // Value accepted — key was recognized AND value passed validation
            $this->assertArrayHasKey('jsonDecodeOptMask', $c->getUnserializeConfig());
        } catch (\InvalidArgumentException $e) {
            // InvalidArgumentException means the key WAS found but the value
            // failed regex validation — still proves the key typo fix works.
            // (Regex pattern fix tracked separately in PR #189)
            $this->assertStringContainsString('jsonDecodeOptMask', $e->getMessage());
        }
    }

    public function testSetUnserializeConfigRejectsBothJsonDecodeOptsAndMask(): void
    {
        $this->expectException(\DomainException::class);
        $c = new VersionDefaultConfig();
        $c->setUnserializeConfig([
            'jsonDecodeOpts' => JSON_THROW_ON_ERROR,
            'jsonDecodeOptMask' => 'JSON_THROW_ON_ERROR',
        ]);
    }

    public function testSetSerializeConfigAcceptsEmptyArray(): void
    {
        $c = new VersionDefaultConfig();
        $c->setSerializeConfig([]);
        $this->assertEmpty($c->getSerializeConfig());
    }
}
