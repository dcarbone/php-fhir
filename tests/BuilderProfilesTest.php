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
use DCarbone\PHPFHIR\Builder;
use DCarbone\PHPFHIR\Config;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class BuilderProfilesTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BuilderProfilesTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function dir(string $name): string
    {
        $path = $this->_tmpdir->path($name);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    private function builder(): Builder
    {
        return new Builder(Config::fromArray([
            'libraryPath' => $this->dir('out'),
            'versions' => [
                [
                    'name' => 'R4',
                    'schemaPath' => $this->dir('r4'),
                    'profiles' => ['packages' => [['name' => 'hl7.fhir.us.core', 'source' => $this->dir('core')]]],
                ],
                ['name' => 'R5', 'schemaPath' => $this->dir('r5')],
            ],
        ]));
    }

    public function testAssertNullPackagesIsNoop(): void
    {
        $this->builder()->assertProfilePackagesConfigured(['R4', 'R5'], null);
        $this->addToAssertionCount(1);
    }

    public function testAssertConfiguredPackagePasses(): void
    {
        $this->builder()->assertProfilePackagesConfigured(['R4'], ['hl7.fhir.us.core']);
        $this->addToAssertionCount(1);
    }

    public function testAssertUnknownPackageThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"nope"');
        $this->builder()->assertProfilePackagesConfigured(['R4'], ['nope']);
    }

    public function testAssertPackageOnlyInUnselectedVersionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->assertProfilePackagesConfigured(['R5'], ['hl7.fhir.us.core']);
    }

    public function testRenderProfilesRejectsUnknownPackage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder()->renderProfiles(['R4'], ['nope']);
    }

    public function testRenderProfilesWithNoConfiguredPackagesIsNoop(): void
    {
        $this->builder()->renderProfiles(['R5']);
        $this->addToAssertionCount(1);
    }
}
