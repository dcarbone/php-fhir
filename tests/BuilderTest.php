<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests;

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

use DCarbone\PHPFHIR\Builder;
use DCarbone\PHPFHIR\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class BuilderTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BuilderTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    /** @return array<string, array{bool, bool, bool}> */
    public static function generationOptionsProvider(): array
    {
        return [
            'core and tests enabled with path' => [true, true, true],
            'core enabled, tests disabled with path' => [true, false, true],
            'core and tests enabled without path' => [true, true, false],
            'core enabled, tests disabled without path' => [true, false, false],
            'core disabled, tests enabled with path' => [false, true, true],
            'core and tests disabled with path' => [false, false, true],
            'core disabled, tests enabled without path' => [false, true, false],
            'core and tests disabled without path' => [false, false, false],
        ];
    }

    #[DataProvider('generationOptionsProvider')]
    public function testRenderHonorsTestGenerationOptions(bool $coreFiles, bool $testFiles, bool $hasTestsPath): void
    {
        $libraryPath = $this->_tmpdir->path('library');
        if (!is_dir($libraryPath)) {
            self::assertTrue(mkdir($libraryPath));
        }
        $testsPath = $hasTestsPath ? $this->_tmpdir->path('tests') : null;
        if (null !== $testsPath && !is_dir($testsPath)) {
            self::assertTrue(mkdir($testsPath));
        }
        $config = new Config(libraryPath: $libraryPath, versions: [], testsPath: $testsPath);
        $builder = $this->getMockBuilder(Builder::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['writeLibraryTestClasses', 'writeFHIRVersionTestClasses', 'writeFHIRVersionTypeTestClasses'])
            ->getMock();

        $builder->expects($coreFiles && $testFiles && $hasTestsPath ? self::once() : self::never())
            ->method('writeLibraryTestClasses');
        foreach (['writeFHIRVersionTestClasses', 'writeFHIRVersionTypeTestClasses'] as $method) {
            $builder->expects($testFiles && $hasTestsPath ? self::once() : self::never())->method($method);
        }

        $builder->render(coreFiles: $coreFiles, testFiles: $testFiles, profiles: false);

        if ($coreFiles) {
            self::assertFileExists(
                $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_CLASSNAME_AUTOLOADER)->getFilepath(),
            );
        }
    }
}
