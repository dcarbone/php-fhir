<?php declare(strict_types=1);

/*
 * Runs a single measured unserialization or serialization in an isolated process so peak memory is not polluted by earlier runs.
 * Not intended to be invoked directly; see run.php.  Prints one JSON object on stdout.
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

function readProcStatusKB(string $key): null|int
{
    $status = @file_get_contents('/proc/self/status');
    if (false === $status || 1 !== preg_match('/^' . $key . ':\s+(\d+) kB/m', $status, $m)) {
        return null;
    }
    return (int)$m[1] * 1024;
}

// Resets the kernel's peak-RSS high-water mark (writing "5" to clear_refs) and returns the current RSS.
function resetAndReadRSS(): null|int
{
    if (false === @file_put_contents('/proc/self/clear_refs', '5')) {
        return null;
    }
    return readProcStatusKB('VmRSS');
}

$opts = getopt('', ['autoloader:', 'namespace:', 'version:', 'strategy:', 'format:', 'fixture:', 'warmup:']);

try {
    loadGenerated($opts['autoloader']);
    $version = newVersion($opts['namespace'], $opts['version']);
    $strategy = strategies($opts['namespace'])[$opts['strategy']] ?? throw new \RuntimeException('unknown strategy');
    $format = $opts['format'];
    $fn = $strategy['fn'];
    $serialize = 'serialize' === ($strategy['op'] ?? 'unserialize');
    $parse = static fn(string $raw): object => 'xml' === $format
        ? ($opts['namespace'] . '\\Encoding\\ResourceParser')::parseXML($version, $raw)
        : ($opts['namespace'] . '\\Encoding\\ResourceParser')::parseJSON($version, $raw);

    $input = (string)file_get_contents($opts['fixture']);
    $bytes = strlen($input);

    // Warm up with one entry of every sample type so class loading is not attributed to the measured run.  Serialization
    // is measured against an already built Bundle, so building it is part of the setup, not the measurement.
    $warm = (string)file_get_contents($opts['warmup']);
    $fn($version, $serialize ? $parse($warm) : $warm, $format);
    unset($warm);
    if ($serialize) {
        $input = $parse($input);
    }

    gc_collect_cycles();
    $baseMem = memory_get_usage();
    if (function_exists('memory_reset_peak_usage')) {
        memory_reset_peak_usage();
    }

    $rssBase = resetAndReadRSS();

    $start = hrtime(true);
    $result = $fn($version, $input, $format);
    $elapsed = (hrtime(true) - $start) / 1e6;

    $peak = memory_get_peak_usage();
    $retained = memory_get_usage() - $baseMem;
    $rssPeak = readProcStatusKB('VmHWM');

    $entries = null;
    if ($serialize) {
        if ('' === $result) {
            throw new \RuntimeException('serialization produced no output');
        }
    } elseif (is_object($result) && method_exists($result, 'getEntry')) {
        $entries = count($result->getEntry());
    }

    echo json_encode([
        'ok' => true,
        'ms' => $elapsed,
        'peakBytes' => max(0, $peak - $baseMem),
        'retainedBytes' => max(0, $retained),
        // libxml allocates outside the PHP memory manager, so RSS is the only fair XML comparison. Linux only.
        'peakRssBytes' => null !== $rssBase && null !== $rssPeak ? max(0, $rssPeak - $rssBase) : null,
        'inputBytes' => $bytes,
        'entries' => $entries,
    ]);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => sprintf('%s: %s', $e::class, $e->getMessage())]);
    exit(1);
}
