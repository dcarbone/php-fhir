<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Bin;

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

/**
 * Exercises the flag handling of bin/generate.php.  Only paths that fail validation or that generate profiles only
 * are run, as neither requires parsing real schema files.
 */
class GenerateScriptTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;
    private string $_config;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'GenerateScriptTest');
        $this->_tmpdir->create();

        foreach (['out', 'r4', 'r5', 'core'] as $d) {
            mkdir($this->_tmpdir->path('') . '/' . $d, 0777, true);
        }
        $root = $this->_tmpdir->path('');
        $this->_config = $root . '/config.php';
        file_put_contents($this->_config, '<?php return ' . var_export([
                'libraryPath' => $root . '/out',
                'versions' => [
                    [
                        'name' => 'R4',
                        'schemaPath' => $root . '/r4',
                        'profiles' => ['packages' => [['name' => 'hl7.fhir.us.core', 'source' => $root . '/core']]],
                    ],
                    ['name' => 'R5', 'schemaPath' => $root . '/r5'],
                ],
            ], true) . ';');
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runScript(string ...$args): array
    {
        $cmd = array_merge(
            [PHP_BINARY, dirname(__DIR__, 2) . '/bin/generate.php', '--config', $this->_config],
            $args,
        );
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proc);
        $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($proc), $out];
    }

    public static function failingArgsProvider(): array
    {
        return [
            'noProfiles + profiles' => [['--noProfiles', '--profiles'], 'cannot be combined'],
            'noProfiles + onlyProfiles' => [['--noProfiles', '--onlyProfiles'], 'cannot be combined'],
            'noProfiles + profilePackages' => [['--noProfiles', '--profilePackages', 'a.b'], 'cannot be combined'],
            'onlyProfiles + onlyCore' => [['--onlyProfiles', '--onlyCore'], 'mutually exclusive'],
            'onlyProfiles + onlyLibrary' => [['--onlyProfiles', '--onlyLibrary'], 'mutually exclusive'],
            'empty profilePackages' => [['--profilePackages', ','], 'requires at least one package name'],
            'unknown package' => [['--onlyProfiles', '--profilePackages', 'nope'], 'is not configured'],
            'profiles on version without packages' => [['--profiles', '--versions', 'R5'], 'have profile packages configured'],
            'onlyProfiles on version without packages' => [['--onlyProfiles', '--versions', 'R5'], 'have profile packages configured'],
            'package only on unselected version' => [['--onlyProfiles', '--versions', 'R4,R5', '--profilePackages', 'x.y'], 'is not configured'],
        ];
    }

    #[DataProvider('failingArgsProvider')]
    public function testInvalidFlagCombinationsFail(array $args, string $expected): void
    {
        [$code, $out] = $this->runScript(...$args);
        $this->assertSame(1, $code, $out);
        $this->assertStringContainsString($expected, $out);
    }

    public function testOnlyProfilesSucceeds(): void
    {
        [$code, $out] = $this->runScript('--onlyProfiles', '--versions', 'R4');
        $this->assertSame(0, $code, $out);
    }

    public function testOnlyProfilesWithPackageFilterSucceeds(): void
    {
        [$code, $out] = $this->runScript('--onlyProfiles', '--profilePackages=hl7.fhir.us.core');
        $this->assertSame(0, $code, $out);
    }

    public function testHelpListsProfileFlags(): void
    {
        [$code, $out] = $this->runScript('--help');
        $this->assertSame(0, $code);
        foreach (['--profiles', '--noProfiles', '--onlyProfiles', '--profilePackages'] as $flag) {
            $this->assertStringContainsString($flag, $out);
        }
    }
}
