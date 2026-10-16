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

    /**
     * Points the R4 version at the real XSD schema and its profile package at a tiny generated package.
     */
    private function useRealSchema(): void
    {
        $schema = dirname(__DIR__, 2) . '/input/R4';
        if (!is_file($schema . '/fhir-base.xsd')) {
            $this->markTestSkipped('R4 schema not available');
        }
        $root = $this->_tmpdir->path('');
        $package = $root . '/core/package';
        mkdir($package, 0777, true);
        file_put_contents($package . '/package.json', json_encode([
            'name' => 'hl7.fhir.us.core',
            'version' => '1.0.0',
            'fhirVersions' => ['4.0.1'],
        ]));
        file_put_contents($package . '/StructureDefinition-my-patient.json', json_encode([
            'resourceType' => 'StructureDefinition',
            'id' => 'my-patient',
            'url' => 'http://example.org/StructureDefinition/my-patient',
            'name' => 'MyPatient',
            'kind' => 'resource',
            'abstract' => false,
            'type' => 'Patient',
            'baseDefinition' => 'http://hl7.org/fhir/StructureDefinition/Patient',
            'derivation' => 'constraint',
            'differential' => ['element' => []],
        ]));
        file_put_contents($this->_config, '<?php return ' . var_export([
                'libraryPath' => $root . '/out',
                'versions' => [
                    [
                        'name' => 'R4',
                        'schemaPath' => $schema,
                        'profiles' => ['packages' => [['name' => 'hl7.fhir.us.core', 'source' => $root . '/core']]],
                    ],
                ],
            ], true) . ';');
    }

    public function testOnlyProfilesGeneratesClasses(): void
    {
        $this->useRealSchema();
        [$code, $out] = $this->runScript('--onlyProfiles', '--versions', 'R4');
        $this->assertSame(0, $code, $out);
        $dir = $this->_tmpdir->path('') . '/out';
        $files = glob($dir . '/*/*/Versions/R4/Profiles/*/MyPatient.php')
            ?: glob($dir . '/*/Versions/R4/Profiles/*/MyPatient.php')
            ?: glob($dir . '/**/MyPatient.php');
        $this->assertNotEmpty($files, $out);
        $this->assertFileExists(dirname($files[0], 2) . '/ProfileMap.php');
        $this->assertFileExists(dirname($files[0], 2) . '/ProfileAutoloader.php');
        $this->assertFileExists(dirname($files[0]) . '/PackageAutoloader.php');
    }

    public function testGeneratedParserResolvesProfiles(): void
    {
        $this->useRealSchema();
        $root = $this->_tmpdir->path('');
        $url = 'http://example.org/StructureDefinition/my-patient';
        // require a name so a bare Patient violates the profile
        file_put_contents($root . '/core/package/StructureDefinition-my-patient.json', json_encode([
            'resourceType' => 'StructureDefinition',
            'id' => 'my-patient',
            'url' => $url,
            'name' => 'MyPatient',
            'kind' => 'resource',
            'abstract' => false,
            'type' => 'Patient',
            'baseDefinition' => 'http://hl7.org/fhir/StructureDefinition/Patient',
            'derivation' => 'constraint',
            'differential' => ['element' => [['id' => 'Patient.name', 'path' => 'Patient.name', 'min' => 1]]],
        ]));
        mkdir($root . '/tests', 0777, true);
        file_put_contents($this->_config, '<?php return ' . var_export([
                'libraryPath' => $root . '/out',
                'testsPath' => $root . '/tests',
                'libraryNamespacePrefix' => '\\Gen',
                'versions' => [
                    [
                        'name' => 'R4',
                        'schemaPath' => dirname(__DIR__, 2) . '/input/R4',
                        'defaultConfig' => ['unserializeConfig' => ['profileResolution' => 'auto']],
                        'profiles' => ['packages' => [['name' => 'hl7.fhir.us.core', 'source' => $root . '/core']]],
                    ],
                ],
            ], true) . ';');
        [$code, $out] = $this->runScript('--versions', 'R4', '--profiles');
        $this->assertSame(0, $code, $out);

        $script = $root . '/parse.php';
        file_put_contents($script, '<?php
            require ' . var_export($root . '/out/Gen/Autoloader.php', true) . ';
            \Gen\Autoloader::register();
            require ' . var_export($root . '/out/Gen/Versions/R4/Profiles/ProfileAutoloader.php', true) . ';
            \Gen\Versions\R4\Profiles\ProfileAutoloader::register();
            $url = ' . var_export($url, true) . ';
            $parse = static function (array $cfg, string $input): string {
                $v = new \Gen\Versions\R4\Version(["unserializeConfig" => $cfg + ["profileResolution" => "auto"]]);
                try {
                    return get_class(\Gen\Encoding\ResourceParser::parse($v, $input));
                } catch (\Throwable $e) {
                    return "EXC " . $e::class;
                }
            };
            $good = ["resourceType" => "Patient", "meta" => ["profile" => [$url . "|1.0.0"]], "name" => [["family" => "F"]]];
            $bad = ["resourceType" => "Patient", "meta" => ["profile" => [$url]]];
            $unknown = ["resourceType" => "Patient", "meta" => ["profile" => ["http://nope"]]];
            $bundle = ["resourceType" => "Bundle", "type" => "collection", "entry" => [["resource" => $good]]];
            $xml = "<Patient xmlns=\"http://hl7.org/fhir\"><meta><profile value=\"" . $url . "\"/></meta><name><family value=\"F\"/></name></Patient>";
            $bundleType = \Gen\Encoding\ResourceParser::parse(new \Gen\Versions\R4\Version(["unserializeConfig" => ["profileResolution" => "auto"]]), json_encode($bundle))
                ->getEntry()[0]->getResource();
            echo json_encode([
                "json" => $parse([], json_encode($good)),
                "xml" => $parse([], $xml),
                "bundle" => get_class($bundleType),
                "off" => $parse(["profileResolution" => "off"], json_encode($good)),
                "none" => $parse([], json_encode(["resourceType" => "Patient"])),
                "unknownFallback" => $parse([], json_encode($unknown)),
                "unknownException" => $parse(["onUnknownProfile" => "exception"], json_encode($unknown)),
                "violationKeep" => $parse([], json_encode($bad)),
                "violationFallback" => $parse(["onProfileViolation" => "fallback"], json_encode($bad)),
                "violationException" => $parse(["onProfileViolation" => "exception"], json_encode($bad)),
            ]);
        ');
        $proc = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proc);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        $res = json_decode($stdout, true);
        $this->assertIsArray($res, "OUT: " . $stdout . " ERR: " . $stderr);

        $profile = 'Gen\\Versions\\R4\\Profiles\\';
        $this->assertStringStartsWith($profile, $res['json']);
        $this->assertStringEndsWith('MyPatient', $res['json']);
        $this->assertSame($res['json'], $res['xml']);
        $this->assertSame($res['json'], $res['bundle']);
        $this->assertSame($res['json'], $res['violationKeep']);
        $this->assertSame('EXC UnexpectedValueException', $res['violationException']);
        $this->assertSame('EXC UnexpectedValueException', $res['unknownException']);

        $base = 'Gen\\Versions\\R4\\Types\\FHIRResource\\FHIRDomainResource\\FHIRPatient';
        foreach (['off', 'none', 'unknownFallback', 'violationFallback'] as $k) {
            $this->assertSame($base, $res[$k], $k);
        }
    }

    public function testOnlyProfilesWithPackageFilterSucceeds(): void
    {
        $this->useRealSchema();
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
