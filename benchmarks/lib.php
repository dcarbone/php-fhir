<?php declare(strict_types=1);

/*
 * Shared helpers for the unserialization benchmark harness.  See run.php for usage.
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

// FHIR release numbers used in the sample file names found in tmp/fhir_test_resources
const SAMPLE_VERSION_NUMBERS = [
    'DSTU2' => '1.0.2',
    'STU3' => '3.0.1',
    'R4' => '4.0.1',
    'R4B' => '4.3.0',
    'R5' => '5.0.0',
];

/**
 * Registry of unserialization strategies.  To benchmark a new approach, add an entry here; each callable receives the
 * generated version object, the raw input string and the format, and must return the unserialized result (or, for
 * "decode", the intermediate tree).
 *
 * @return array<string, array{desc: string, formats: string[], fn: callable}>
 */
function strategies(string $ns): array
{
    $parser = $ns . '\\Encoding\\ResourceParser';
    return [
        'decode' => [
            'desc' => 'Built-in decode only (SimpleXMLElement / json_decode); no FHIR objects. Lower bound.',
            'formats' => ['xml', 'json'],
            'fn' => static function (object $version, string $input, string $format): mixed {
                $cfg = $version->getConfig()->getUnserializeConfig();
                if ('xml' === $format) {
                    return new \SimpleXMLElement($input, $cfg->getLibxmlOpts());
                }
                return json_decode($input, false, $cfg->getJSONDecodeMaxDepth(), $cfg->getJSONDecodeOpts());
            },
        ],
        'tree' => [
            'desc' => 'Current behavior: decode into a full tree, then build FHIR objects (ResourceParser).',
            'formats' => ['xml', 'json'],
            'fn' => static fn(object $version, string $input, string $format): mixed => 'xml' === $format
                ? $parser::parseXML($version, $input)
                : $parser::parseJSON($version, $input),
        ],
    ];
}

function loadGenerated(string $autoloader): void
{
    if (!is_file($autoloader)) {
        throw new \RuntimeException(sprintf(
            'Generated autoloader not found at "%s". Generate code first (bin/generate.sh) or pass --autoloader.',
            $autoloader
        ));
    }
    require_once $autoloader;
}

function newVersion(string $ns, string $versionName): object
{
    $class = sprintf('%s\\Versions\\%s\\Version', $ns, $versionName);
    if (!class_exists($class)) {
        throw new \RuntimeException(sprintf('Version class "%s" not found; has "%s" been generated?', $class, $versionName));
    }
    return new $class();
}

function wrapBundleXML(string $entries): string
{
    return '<Bundle xmlns="http://hl7.org/fhir"><type value="collection"/>' . $entries . '</Bundle>';
}

function bundleEntryXML(string $rawResource): string
{
    return '<entry><resource>' . $rawResource . '</resource></entry>';
}

function wrapBundleJSON(string $entries): string
{
    return '{"resourceType":"Bundle","type":"collection","entry":[' . $entries . ']}';
}

function bundleEntryJSON(string $rawResource): string
{
    return '{"resource":' . $rawResource . '}';
}

function stripXMLProlog(string $xml): string
{
    return trim((string)preg_replace('/^\s*<\?xml[^>]*\?>/', '', $xml));
}

/**
 * Returns true if the raw resource unserializes cleanly (as the sole entry of a bundle) in the provided format.
 */
function resourceParses(string $ns, object $version, string $format, string $raw): bool
{
    $parser = $ns . '\\Encoding\\ResourceParser';
    try {
        $bundle = 'xml' === $format
            ? $parser::parseXML($version, wrapBundleXML(bundleEntryXML($raw)))
            : $parser::parseJSON($version, wrapBundleJSON(bundleEntryJSON($raw)));
        return 1 === count($bundle->getEntry());
    } catch (\Throwable) {
        return false;
    }
}

/**
 * Locates sample resources and returns them in both formats.  A sample available in only one format has its
 * counterpart derived by round-tripping through the generated types, so this works for versions whose sample set is
 * lopsided.  Samples that fail to unserialize in either format are discarded.
 *
 * $maxBytes (when > 0) discards samples larger than that, and $onePerType keeps only the first sample (by name) of each
 * resource type; both exist so large example sets can be reduced to a small, varied fixture set.
 *
 * @return array{names: string[], xml: string[], json: string[]} Parallel lists of sample names and raw resource strings.
 */
function loadSamples(string $ns, object $version, string $samplesDir, string $fileSuffix = '', int $maxEntriesPerBundle = 3, int $maxBytes = 0, bool $onePerType = false): array
{
    $parser = $ns . '\\Encoding\\ResourceParser';
    $suffix = $fileSuffix;

    $names = [];
    foreach (['xml', 'json'] as $ext) {
        foreach (glob(sprintf('%s/*%s.%s', $samplesDir, $suffix, $ext)) ?: [] as $file) {
            $names[basename($file, '.' . $ext)] = true;
        }
    }
    ksort($names);

    $samples = ['names' => [], 'xml' => [], 'json' => []];
    $seenTypes = [];
    $add = static function (string $name, string $xml, string $json) use (&$samples, &$seenTypes, $ns, $version, $maxBytes, $onePerType): void {
        if ($maxBytes > 0 && (strlen($xml) > $maxBytes || strlen($json) > $maxBytes)) {
            return;
        }
        // nested bundles are not representative of the entry payload
        if ('Bundle' === (json_decode($json)->resourceType ?? null)) {
            return;
        }
        if ($onePerType) {
            $type = preg_match('/"resourceType"\s*:\s*"(\w+)"/', $json, $m) ? $m[1] : $name;
            if (isset($seenTypes[$type])) {
                return;
            }
        }
        if (resourceParses($ns, $version, 'xml', $xml) && resourceParses($ns, $version, 'json', $json)) {
            $seenTypes[$type ?? $name] = true;
            $samples['names'][] = $name;
            $samples['xml'][] = $xml;
            $samples['json'][] = $json;
        }
    };

    foreach (array_keys($names) as $name) {
        $base = sprintf('%s/%s', $samplesDir, $name);
        if ($maxBytes > 0 && max(@filesize($base . '.xml') ?: 0, @filesize($base . '.json') ?: 0) > $maxBytes * 4) {
            // generous pre-filter; the exact check happens on the (smaller) re-serialized resource
            continue;
        }
        $xml = is_file($base . '.xml') ? stripXMLProlog((string)file_get_contents($base . '.xml')) : null;
        $json = is_file($base . '.json') ? trim((string)file_get_contents($base . '.json')) : null;
        $sampleName = (string)preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)preg_replace('/\(.*\)$/', '', substr($name, 0, strlen($name) - strlen($suffix))));

        try {
            if (preg_match('/"resourceType"\s*:\s*"Bundle"/', (string)$json) || (null === $json && str_starts_with((string)$xml, '<Bundle'))) {
                // Many HL7 examples are bundles; unwrap a handful of their entries as individual samples.
                $bundle = null !== $json ? $parser::parseJSON($version, $json) : $parser::parseXML($version, $xml);
                $i = 0;
                foreach ($bundle->getEntry() as $entry) {
                    $resource = $entry->getResource();
                    if (null === $resource || $i >= $maxEntriesPerBundle) {
                        continue;
                    }
                    $add(
                        sprintf('%s-%d', $sampleName, $i++),
                        stripXMLProlog($resource->xmlSerialize()->outputMemory()),
                        json_encode($resource, JSON_THROW_ON_ERROR),
                    );
                }
                continue;
            }
            if (null === $json) {
                $json = json_encode($parser::parseXML($version, $xml), JSON_THROW_ON_ERROR);
            } elseif (null === $xml) {
                $xml = stripXMLProlog($parser::parseJSON($version, $json)->xmlSerialize()->outputMemory());
            }
        } catch (\Throwable) {
            continue;
        }
        $add($sampleName, $xml, $json);
    }
    if ([] === $samples['json']) {
        throw new \RuntimeException(sprintf('No usable sample resources found in "%s" (file suffix "%s").', $samplesDir, $suffix));
    }
    return $samples;
}

/**
 * Writes a fixture bundle with $entries entries to $path, cycling through the samples.  Entries are streamed to disk so
 * that fixture generation does not hold the whole document in memory.
 */
function writeFixture(string $path, string $format, array $rawSamples, int $entries): void
{
    $fh = fopen($path, 'wb');
    if (false === $fh) {
        throw new \RuntimeException(sprintf('Unable to open "%s" for writing', $path));
    }
    $count = count($rawSamples);
    if ('xml' === $format) {
        fwrite($fh, '<Bundle xmlns="http://hl7.org/fhir"><type value="collection"/>');
        for ($i = 0; $i < $entries; $i++) {
            fwrite($fh, bundleEntryXML($rawSamples[$i % $count]));
        }
        fwrite($fh, '</Bundle>');
    } else {
        fwrite($fh, '{"resourceType":"Bundle","type":"collection","entry":[');
        for ($i = 0; $i < $entries; $i++) {
            fwrite($fh, ($i > 0 ? ',' : '') . bundleEntryJSON($rawSamples[$i % $count]));
        }
        fwrite($fh, ']}');
    }
    fclose($fh);
}

/**
 * Writes each usable sample as <name>.xml and <name>.json into $dir, for committing as self-contained fixtures.
 */
function exportFixtures(array $samples, string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new \RuntimeException(sprintf('Unable to create "%s"', $dir));
    }
    foreach ($samples['names'] as $i => $name) {
        file_put_contents(sprintf('%s/%s.xml', $dir, $name), $samples['xml'][$i] . "\n");
        file_put_contents(sprintf('%s/%s.json', $dir, $name), $samples['json'][$i] . "\n");
    }
}

/**
 * Runs one isolated measurement.
 *
 * @param array{autoloader: string, namespace: string, version: string} $cfg
 * @return array{ms: float, peakBytes: int, retainedBytes: int, peakRssBytes: null|int, inputBytes: int, entries: null|int}
 * @throws \RuntimeException If the worker fails.
 */
function runWorker(array $cfg, string $strategy, string $format, string $fixture, string $warmup): array
{
    $cmd = [
        PHP_BINARY, '-d', 'memory_limit=-1', __DIR__ . '/worker.php',
        '--autoloader=' . $cfg['autoloader'], '--namespace=' . $cfg['namespace'], '--version=' . $cfg['version'],
        '--strategy=' . $strategy, '--format=' . $format, '--fixture=' . $fixture,
        '--warmup=' . $warmup,
    ];
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($proc)) {
        throw new \RuntimeException('Unable to start benchmark worker');
    }
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    proc_close($proc);
    $r = json_decode((string)$out, true);
    if (!is_array($r) || !($r['ok'] ?? false)) {
        throw new \RuntimeException(sprintf('%s/%s failed: %s', $strategy, $format, $r['error'] ?? ($err ?: $out)));
    }
    return $r;
}

/**
 * Runs the full strategy x format x size matrix.
 *
 * @param array{autoloader: string, namespace: string, version: string, fixtureDir: string, strategies: string[], formats: string[], sizes: int[], iterations: int} $cfg
 * @param array{xml: string[], json: string[]} $samples
 * @return list<array<string, mixed>>
 */
function runMatrix(array $cfg, array $samples, null|callable $onWarning = null): array
{
    $all = strategies($cfg['namespace']);
    $results = [];
    foreach ($cfg['sizes'] as $size) {
        foreach ($cfg['formats'] as $format) {
            $fixture = ensureFixture($cfg['fixtureDir'], $format, $samples[$format], $size);
            $warmup = ensureFixture($cfg['fixtureDir'], $format, $samples[$format], count($samples[$format]), 'warmup');
            foreach ($cfg['strategies'] as $name) {
                if (!in_array($format, $all[$name]['formats'], true)) {
                    continue;
                }
                $runs = [];
                for ($i = 0; $i < $cfg['iterations']; $i++) {
                    $r = runWorker($cfg, $name, $format, $fixture, $warmup);
                    if (null !== $r['entries'] && $r['entries'] !== $size && null !== $onWarning) {
                        $onWarning(sprintf('%s/%s/%d: expected %d entries, got %d', $name, $format, $size, $size, $r['entries']));
                    }
                    $runs[] = $r;
                }
                $results[] = summarize($name, $format, $size, $runs);
            }
        }
    }
    return $results;
}

function ensureFixture(string $dir, string $format, array $rawSamples, int $entries, string $prefix = 'bundle'): string
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new \RuntimeException(sprintf('Unable to create "%s"', $dir));
    }
    $path = sprintf('%s/%s-%d.%s', $dir, $prefix, $entries, $format);
    writeFixture($path, $format, $rawSamples, $entries);
    return $path;
}

/**
 * Median time, worst-case memory.
 */
function summarize(string $strategy, string $format, int $size, array $runs): array
{
    $times = array_column($runs, 'ms');
    sort($times);
    return [
        'key' => resultKey($strategy, $format, $size),
        'strategy' => $strategy,
        'format' => $format,
        'entries' => $size,
        'inputBytes' => $runs[0]['inputBytes'],
        'ms' => $times[intdiv(count($times), 2)],
        'peakBytes' => max(array_column($runs, 'peakBytes')),
        'retainedBytes' => max(array_column($runs, 'retainedBytes')),
        'peakRssBytes' => null === $runs[0]['peakRssBytes'] ? null : max(array_column($runs, 'peakRssBytes')),
    ];
}

function resultKey(string $strategy, string $format, int $size): string
{
    return sprintf('%s|%s|%d', $strategy, $format, $size);
}

function baselinePath(string $version): string
{
    return sprintf('%s/baselines/%s-php%d.%d.json', __DIR__, $version, PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
}

function fixturesPath(string $version): string
{
    return sprintf('%s/fixtures/%s', __DIR__, $version);
}
