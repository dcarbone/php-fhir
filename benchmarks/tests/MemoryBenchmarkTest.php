<?php declare(strict_types=1);

/*
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

namespace DCarbone\PHPFHIRTests\Benchmarks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function DCarbone\PHPFHIR\Benchmarks\baselinePath;
use function DCarbone\PHPFHIR\Benchmarks\ensureFixture;
use function DCarbone\PHPFHIR\Benchmarks\fixturesPath;
use function DCarbone\PHPFHIR\Benchmarks\loadSamples;
use function DCarbone\PHPFHIR\Benchmarks\newVersion;
use function DCarbone\PHPFHIR\Benchmarks\runWorker;

require_once __DIR__ . '/../lib.php';

/**
 * Guards against memory regressions in unserialization and serialization.
 *
 * PHP-allocator memory is deterministic for a given PHP minor version and fixture set, so measurements are compared to
 * a committed baseline (benchmarks/baselines/<version>-php<major.minor>.json).  Wall-clock time is intentionally NOT
 * asserted here; it is far too noisy on shared hardware and is instead trended by the "Benchmarks" CI workflow.
 *
 * Improvements never fail the suite.  To lock one in, or to accept an intentional regression, regenerate the baseline:
 *
 *   php benchmarks/run.php --version=<version> --sizes=200,1000 --iterations=3 --update-baseline
 *
 * Tolerance may be adjusted with the PHPFHIR_BENCH_TOLERANCE env var (fraction, default 0.05).
 */
#[Group('benchmark')]
class MemoryBenchmarkTest extends TestCase
{
    private const NAMESPACE = 'DCarbone\\PHPFHIRGenerated';
    private const ABSOLUTE_SLACK_BYTES = 262144;
    // Below this, a baseline is not meaningful (e.g. SimpleXML allocates outside of PHP's memory manager).
    private const MIN_MEANINGFUL_BYTES = 1048576;

    /** @var array<string, array<string, string>> */
    private static array $fixtures = [];
    private static null|array $samples = null;

    public static function baselineProvider(): iterable
    {
        $path = baselinePath(self::version());
        if (!is_file($path)) {
            // an empty provider is an error in PHPUnit; yield a placeholder that the test reports as skipped
            yield 'missing baseline' => [null];
            return;
        }
        foreach (json_decode((string)file_get_contents($path), true)['results'] as $r) {
            yield $r['key'] => [$r];
        }
    }

    private static function version(): string
    {
        return (string)getenv('PHPFHIR_TEST_TARGET');
    }

    #[DataProvider('baselineProvider')]
    public function testMemoryDoesNotRegress(null|array $baseline): void
    {
        if (null === $baseline) {
            $this->markTestSkipped(sprintf('No benchmark baseline at "%s"; generate one with benchmarks/run.php --update-baseline.', baselinePath(self::version())));
        }
        $versionName = self::version();
        $tolerance = (float)(getenv('PHPFHIR_BENCH_TOLERANCE') ?: '0.05');
        $cfg = [
            'autoloader' => PHPFHIR_TEST_GENERATED_AUTOLOADER_PATH,
            'namespace' => self::NAMESPACE,
            'version' => $versionName,
        ];

        [$fixture, $warmup] = $this->fixtures($versionName, $baseline['format'], $baseline['entries']);
        $actual = runWorker($cfg, $baseline['strategy'], $baseline['format'], $fixture, $warmup);

        $this->assertSame($baseline['inputBytes'], $actual['inputBytes'], 'Fixture content differs from baseline; regenerate the baseline.');

        foreach (['peakBytes', 'retainedBytes'] as $metric) {
            if ($baseline[$metric] < self::MIN_MEANINGFUL_BYTES) {
                continue;
            }
            $limit = (int)($baseline[$metric] * (1 + $tolerance)) + self::ABSOLUTE_SLACK_BYTES;
            $this->assertLessThanOrEqual(
                $limit,
                $actual[$metric],
                sprintf(
                    '%s %s regressed: %.2f MB vs baseline %.2f MB (allowed %.2f MB, %+.1f%%)',
                    $baseline['key'],
                    $metric,
                    $actual[$metric] / 1048576,
                    $baseline[$metric] / 1048576,
                    $limit / 1048576,
                    ($actual[$metric] - $baseline[$metric]) / $baseline[$metric] * 100,
                ),
            );
        }
    }

    /**
     * @return array{string, string} Fixture path and warm-up path.
     */
    private function fixtures(string $versionName, string $format, int $entries): array
    {
        if (null === self::$samples) {
            self::$samples = loadSamples(self::NAMESPACE, newVersion(self::NAMESPACE, $versionName), fixturesPath($versionName));
        }
        $dir = sprintf('%s/phpfhir-bench-%s-%d', sys_get_temp_dir(), $versionName, getmypid());
        $key = $format . $entries;
        self::$fixtures[$key] ??= [
            ensureFixture($dir, $format, self::$samples[$format], $entries),
            ensureFixture($dir, $format, self::$samples[$format], count(self::$samples[$format]), 'warmup'),
        ];
        return self::$fixtures[$key];
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$fixtures as [$fixture, $warmup]) {
            @unlink($fixture);
            @unlink($warmup);
            @rmdir(dirname($fixture));
        }
        self::$fixtures = [];
    }
}
