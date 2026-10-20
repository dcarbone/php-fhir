<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Utilities;

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

use DCarbone\PHPFHIR\Utilities\FileUtils;
use PHPUnit\Framework\TestCase;

class FileUtilsTest extends TestCase
{
    public function testBuildClassFilepath(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame("/out{$ds}Acme{$ds}Gen{$ds}Thing.php", FileUtils::buildClassFilepath('/out', '\\Acme\\Gen\\Thing'));
        $this->assertSame("/out{$ds}Thing.php", FileUtils::buildClassFilepath('/out', 'Thing'));
    }

    public function testAssertPathReadableAcceptsFilesAndDirectories(): void
    {
        FileUtils::assertPathReadable(__FILE__, 'File');
        FileUtils::assertPathReadable(__DIR__, 'Directory');
        $this->addToAssertionCount(2);
    }

    public function testAssertPathReadableRejectsMissingPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source path "' . __DIR__ . '/nope" either does not exist or is not readable.');
        FileUtils::assertPathReadable(__DIR__ . '/nope', 'Source path');
    }

    public function testAssertPathReadableRejectsEmptyPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FileUtils::assertPathReadable(' ', 'Source path');
    }
}
