<?php declare(strict_types=1);

/*
 * Unserialization / serialization benchmark harness.
 *
 * Builds large synthetic Bundle fixtures (XML and JSON) from the sample resources in benchmarks/fixtures/<version>,
 * then measures each unserialization strategy in an isolated PHP process, reporting time, peak memory and retained
 * memory.  Samples available in only one format are converted to the other using the generated types.
 *
 * Requires code to have been generated first (e.g. `./bin/generate.sh --versions STU3`).
 *
 * Usage:
 *   php benchmarks/run.php [options]
 *
 * Options:
 *   --version=STU3            Generated FHIR version to benchmark
 *   --namespace=<ns>          Generated root namespace (default: DCarbone\PHPFHIRGenerated)
 *   --autoloader=<path>       Generated Autoloader.php (default: output/src/DCarbone/PHPFHIRGenerated/Autoloader.php)
 *   --samples=<dir>           Use raw HL7 examples from this directory instead of benchmarks/fixtures/<version>,
 *                             e.g. tmp/fhir_test_resources (files must be named <Name>-v<release>.{xml,json})
 *   --release=<n.n.n>         FHIR release number in sample file names when using --samples (default derived from --version)
 *   --no-suffix               Sample files are named <Name>.{xml,json} with no -v<release> suffix (e.g. HL7 examples.zip)
 *   --sizes=100,1000,5000     Bundle entry counts to test
 *   --formats=xml,json        Formats to test
 *   --strategies=decode,tree  Strategies to test (see lib.php)
 *   --iterations=3            Runs per combination; median time and max memory are reported
 *   --save=<file>             Write results as JSON
 *   --compare=<file>          Compare against a previously saved results file
 *   --update-baseline         Save results as benchmarks/baselines/<version>-php<major.minor>.json (used by the PHPUnit
 *                             benchmark suite; memory is deterministic per PHP minor version, time is not asserted)
 *   --export-fixtures         Convert the --samples directory into benchmarks/fixtures/<version> and exit; keeps one
 *                             sample per resource type no larger than --max-sample-bytes (default 100000)
 *   --github-action-benchmark=<file>
 *                             Write results in github-action-benchmark "customSmallerIsBetter" format
 *   --list                    List strategies and exit
 *
 * Copyright 2018-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

namespace DCarbone\PHPFHIR\Benchmarks;

require __DIR__ . '/lib.php';

$root = dirname(__DIR__);
$o = getopt('', [
    'version::', 'namespace::', 'autoloader::', 'samples::', 'release::', 'sizes::', 'formats::',
    'strategies::', 'iterations::', 'save::', 'compare::', 'update-baseline', 'export-fixtures',
    'github-action-benchmark::', 'max-sample-bytes::', 'no-suffix', 'list',
]);

$versionName = $o['version'] ?? 'STU3';
$ns = ltrim($o['namespace'] ?? 'DCarbone\\PHPFHIRGenerated', '\\');
$autoloader = $o['autoloader'] ?? $root . '/output/src/DCarbone/PHPFHIRGenerated/Autoloader.php';
$sizes = array_map('intval', explode(',', $o['sizes'] ?? '100,1000,5000'));
$formats = explode(',', $o['formats'] ?? 'xml,json');
$iterations = max(1, (int)($o['iterations'] ?? 3));

loadGenerated($autoloader);
$allStrategies = strategies($ns);

if (isset($o['list'])) {
    foreach ($allStrategies as $name => $s) {
        printf("%-10s %s\n", $name, $s['desc']);
    }
    exit(0);
}

$strategyNames = explode(',', $o['strategies'] ?? implode(',', array_keys($allStrategies)));
foreach ($strategyNames as $name) {
    if (!isset($allStrategies[$name])) {
        fwrite(STDERR, sprintf("Unknown strategy \"%s\"\n", $name));
        exit(2);
    }
}

$version = newVersion($ns, $versionName);

if (isset($o['samples'])) {
    $suffix = '';
    if (!isset($o['no-suffix'])) {
        $suffix = '-v' . ($o['release'] ?? SAMPLE_VERSION_NUMBERS[$versionName] ?? throw new \InvalidArgumentException('Unknown --version; pass --release or --no-suffix'));
    }
    $export = isset($o['export-fixtures']);
    $samples = loadSamples(
        $ns, $version, $o['samples'], $suffix, 3,
        $export ? (int)($o['max-sample-bytes'] ?? 100000) : 0,
        $export,
    );
} else {
    $samples = loadSamples($ns, $version, fixturesPath($versionName));
}

if (isset($o['export-fixtures'])) {
    exportFixtures($samples, fixturesPath($versionName));
    printf("Exported %d fixture(s) to %s\n", count($samples['names']), fixturesPath($versionName));
    exit(0);
}

printf("PHP %s | %s | %d usable sample resources | %d iteration(s)\n\n",
    PHP_VERSION, $versionName, count($samples['json']), $iterations);

$results = runMatrix([
    'autoloader' => $autoloader,
    'namespace' => $ns,
    'version' => $versionName,
    'fixtureDir' => sprintf('%s/tmp/bench/%s', $root, $versionName),
    'strategies' => $strategyNames,
    'formats' => $formats,
    'sizes' => $sizes,
    'iterations' => $iterations,
], $samples, static fn(string $m) => fwrite(STDERR, "WARNING $m\n"));

$baseline = [];
if (isset($o['compare'])) {
    foreach (json_decode((string)file_get_contents($o['compare']), true)['results'] ?? [] as $b) {
        $baseline[$b['key']] = $b;
    }
}

$mb = static fn(int|float $b): string => sprintf('%.1f', $b / 1048576);
$delta = static fn(float $new, float $old): string => 0.0 === $old ? '' : sprintf(' (%+.0f%%)', ($new - $old) / $old * 100);

printf("%-10s %-5s %8s %9s %16s %16s %16s %16s\n", 'strategy', 'fmt', 'entries', 'input MB', 'time ms', 'peak MB', 'retained MB', 'peak RSS MB');
echo str_repeat('-', 103), "\n";
foreach ($results as $r) {
    $b = $baseline[$r['key']] ?? null;
    printf("%-10s %-5s %8d %9s %16s %16s %16s %16s\n",
        $r['strategy'], $r['format'], $r['entries'], $mb($r['inputBytes']),
        sprintf('%.1f', $r['ms']) . ($b ? $delta($r['ms'], $b['ms']) : ''),
        $mb($r['peakBytes']) . ($b ? $delta($r['peakBytes'], $b['peakBytes']) : ''),
        $mb($r['retainedBytes']) . ($b ? $delta($r['retainedBytes'], $b['retainedBytes']) : ''),
        null === $r['peakRssBytes'] ? 'n/a' : $mb($r['peakRssBytes']) . ($b && $b['peakRssBytes'] ? $delta($r['peakRssBytes'], $b['peakRssBytes']) : ''));
}

$saveJSON = json_encode([
    'php' => PHP_VERSION, 'version' => $versionName, 'results' => $results,
], JSON_PRETTY_PRINT) . "\n";

$savePath = isset($o['update-baseline']) ? baselinePath($versionName) : ($o['save'] ?? null);
if (null !== $savePath) {
    if (!is_dir(dirname($savePath)) && !mkdir(dirname($savePath), 0777, true) && !is_dir(dirname($savePath))) {
        throw new \RuntimeException(sprintf('Unable to create "%s"', dirname($savePath)));
    }
    file_put_contents($savePath, $saveJSON);
    printf("\nSaved results to %s\n", $savePath);
}

if (isset($o['github-action-benchmark'])) {
    $series = [];
    foreach ($results as $r) {
        $label = sprintf('%s %s %s %d entries', $versionName, $r['strategy'], $r['format'], $r['entries']);
        $series[] = ['name' => $label . ' - time', 'unit' => 'ms', 'value' => round($r['ms'], 2)];
        $series[] = ['name' => $label . ' - peak memory', 'unit' => 'MB', 'value' => round($r['peakBytes'] / 1048576, 2)];
        $series[] = ['name' => $label . ' - retained memory', 'unit' => 'MB', 'value' => round($r['retainedBytes'] / 1048576, 2)];
        if (null !== $r['peakRssBytes']) {
            $series[] = ['name' => $label . ' - peak RSS', 'unit' => 'MB', 'value' => round($r['peakRssBytes'] / 1048576, 2)];
        }
    }
    file_put_contents($o['github-action-benchmark'], json_encode($series, JSON_PRETTY_PRINT) . "\n");
}
