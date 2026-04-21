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

use DCarbone\PHPFHIR\Version\SourceMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SourceMetadataTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a SourceMetadata instance with its internal state pre-populated so
     * that _compile() is never invoked (and no real XSD file is needed).
     *
     * ReflectionProperty::setAccessible() has been a no-op since PHP 8.1 and is
     * deprecated in PHP 8.3 – we do not call it here.
     */
    private function buildMetadata(string $fhirVersion): SourceMetadata
    {
        $rc = new \ReflectionClass(SourceMetadata::class);

        /** @var SourceMetadata $instance */
        $instance = $rc->newInstanceWithoutConstructor();

        $rc->getProperty('_fhirVersion')->setValue($instance, $fhirVersion);

        // Replicate the pre-release parsing that _compile() performs.
        $dashPos = strpos($fhirVersion, '-');
        if (false !== $dashPos) {
            $rc->getProperty('_fhirVersionBase')->setValue($instance, substr($fhirVersion, 0, $dashPos));
            $rc->getProperty('_fhirPreRelease')->setValue($instance, substr($fhirVersion, $dashPos + 1));
        } else {
            $rc->getProperty('_fhirVersionBase')->setValue($instance, $fhirVersion);
            $rc->getProperty('_fhirPreRelease')->setValue($instance, null);
        }

        $rc->getProperty('_compiled')->setValue($instance, true);

        return $instance;
    }

    // -------------------------------------------------------------------------
    // getSemanticVersion
    // -------------------------------------------------------------------------

    #[DataProvider('provideSemanticVersionUntrimmedCases')]
    public function testGetSemanticVersionUntrimmed(string $raw, string $expected): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expected, $meta->getSemanticVersion(false));
    }

    #[DataProvider('provideSemanticVersionTrimmedCases')]
    public function testGetSemanticVersionTrimmed(string $raw, string $expected): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expected, $meta->getSemanticVersion(true));
    }

    public static function provideSemanticVersionUntrimmedCases(): array
    {
        return [
            'DSTU1'      => ['v0.0.82',        'v0.0.82'],
            'DSTU2'      => ['v1.0.2',          'v1.0.2'],
            'STU3'       => ['v3.0.2',          'v3.0.2'],
            'R4'         => ['v4.0.1',          'v4.0.1'],
            'R4B'        => ['v4.3.0',          'v4.3.0'],
            'R5'         => ['v5.0.0',          'v5.0.0'],
            'R6 ballot4' => ['v6.0.0-ballot4',  'v6.0.0-ballot4'],
        ];
    }

    public static function provideSemanticVersionTrimmedCases(): array
    {
        return [
            'DSTU1'      => ['v0.0.82',        '0.0.82'],
            'DSTU2'      => ['v1.0.2',          '1.0.2'],
            'STU3'       => ['v3.0.2',          '3.0.2'],
            'R4'         => ['v4.0.1',          '4.0.1'],
            'R4B'        => ['v4.3.0',          '4.3.0'],
            'R5'         => ['v5.0.0',          '5.0.0'],
            'R6 ballot4' => ['v6.0.0-ballot4',  '6.0.0-ballot4'],
        ];
    }

    // -------------------------------------------------------------------------
    // getShortVersion
    // -------------------------------------------------------------------------

    #[DataProvider('provideShortVersionCases')]
    public function testGetShortVersion(string $raw, string $expected): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expected, $meta->getShortVersion());
    }

    public static function provideShortVersionCases(): array
    {
        // [ raw stored value, expected short version (Major.Minor only) ]
        return [
            'DSTU1'      => ['v0.0.82',        '0.0'],
            'DSTU2'      => ['v1.0.2',          '1.0'],
            'STU3'       => ['v3.0.2',          '3.0'],
            'R4'         => ['v4.0.1',          '4.0'],
            'R4B'        => ['v4.3.0',          '4.3'],
            'R5'         => ['v5.0.0',          '5.0'],
            // Pre-release suffix is stripped before the dot-count; result is still Major.Minor.
            'R6 ballot4' => ['v6.0.0-ballot4',  '6.0'],
        ];
    }

    // -------------------------------------------------------------------------
    // getVersionInteger
    // The format string "%'.-08s" effectively right-zero-pads the stripped digit
    // string to 8 characters, then intval() reads the full numeric value.
    //
    //   'v5.0.0'  -> strip -> '500'  -> right-pad to 8 -> '50000000' -> 50000000
    //   'v0.0.82' -> strip -> '0082' -> right-pad to 8 -> '00820000' ->   820000
    //
    // Pre-release versions use the base version, so 'v6.0.0-ballot4' and a clean
    // 'v6.0.0' both produce 60000000.  Use isPreRelease() to tell them apart.
    // -------------------------------------------------------------------------

    #[DataProvider('provideVersionIntegerCases')]
    public function testGetVersionInteger(string $raw, int $expected): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expected, $meta->getVersionInteger());
    }

    public static function provideVersionIntegerCases(): array
    {
        return [
            'DSTU1'      => ['v0.0.82',          820000],
            'DSTU2'      => ['v1.0.2',          10200000],
            'STU3'       => ['v3.0.2',          30200000],
            'R4'         => ['v4.0.1',          40100000],
            'R4B'        => ['v4.3.0',          43000000],
            'R5'         => ['v5.0.0',          50000000],
            // Uses base version 'v6.0.0', identical to a GA v6.0.0 release.
            'R6 ballot4' => ['v6.0.0-ballot4',  60000000],
        ];
    }

    // -------------------------------------------------------------------------
    // is* range helpers
    // -------------------------------------------------------------------------

    #[DataProvider('provideIsDSTU1Cases')]
    public function testIsDSTU1(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isDSTU1());
    }

    public static function provideIsDSTU1Cases(): array
    {
        return [
            'v0.0.82 is DSTU1'   => ['v0.0.82', true],
            'v1.0.2 not DSTU1'   => ['v1.0.2',  false],
            'v3.0.2 not DSTU1'   => ['v3.0.2',  false],
            'v5.0.0 not DSTU1'   => ['v5.0.0',  false],
        ];
    }

    #[DataProvider('provideIsDSTU2Cases')]
    public function testIsDSTU2(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isDSTU2());
    }

    public static function provideIsDSTU2Cases(): array
    {
        return [
            'v0.0.82 not DSTU2'  => ['v0.0.82', false],
            'v1.0.2 is DSTU2'    => ['v1.0.2',  true],
            'v2.1.0 is DSTU2'    => ['v2.1.0',  true],
            'v3.0.2 not DSTU2'   => ['v3.0.2',  false],
        ];
    }

    #[DataProvider('provideIsSTU3Cases')]
    public function testIsSTU3(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isSTU3());
    }

    public static function provideIsSTU3Cases(): array
    {
        return [
            'v1.0.2 not STU3'    => ['v1.0.2', false],
            'v3.0.2 is STU3'     => ['v3.0.2', true],
            'v3.5.0 is STU3'     => ['v3.5.0', true],
            'v4.0.1 not STU3'    => ['v4.0.1', false],
        ];
    }

    #[DataProvider('provideIsR4Cases')]
    public function testIsR4(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isR4());
    }

    public static function provideIsR4Cases(): array
    {
        return [
            'v3.0.2 not R4'      => ['v3.0.2', false],
            'v4.0.1 is R4'       => ['v4.0.1', true],
            'v4.0.0 is R4'       => ['v4.0.0', true],
            'v4.3.0 not R4'      => ['v4.3.0', false],
            'v5.0.0 not R4'      => ['v5.0.0', false],
        ];
    }

    #[DataProvider('provideIsR4BCases')]
    public function testIsR4B(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isR4B());
    }

    public static function provideIsR4BCases(): array
    {
        return [
            'v4.0.1 not R4B'     => ['v4.0.1', false],
            'v4.3.0 is R4B'      => ['v4.3.0', true],
            'v4.6.0 is R4B'      => ['v4.6.0', true],
            'v5.0.0 not R4B'     => ['v5.0.0', false],
        ];
    }

    #[DataProvider('provideIsR5Cases')]
    public function testIsR5(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isR5());
    }

    public static function provideIsR5Cases(): array
    {
        return [
            'v4.3.0 not R5'         => ['v4.3.0',         false],
            'v5.0.0 is R5'          => ['v5.0.0',          true],
            'v5.0.1 is R5'          => ['v5.0.1',          true],
            // ballot uses the base version 'v6.0.0' for the range check,
            // which is NOT in [5.0.0, 6.0.0).
            'v6.0.0-ballot4 not R5' => ['v6.0.0-ballot4', false],
        ];
    }

    #[DataProvider('provideIsR6Cases')]
    public function testIsR6(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isR6());
    }

    public static function provideIsR6Cases(): array
    {
        return [
            'v5.0.0 not R6'         => ['v5.0.0',          false],
            'v6.0.0-ballot4 is R6'  => ['v6.0.0-ballot4',  true],
        ];
    }

    // -------------------------------------------------------------------------
    // getPreRelease / isPreRelease
    // -------------------------------------------------------------------------

    #[DataProvider('providePreReleaseCases')]
    public function testGetPreRelease(string $raw, null|string $expectedSuffix): void
    {
        $this->assertSame($expectedSuffix, $this->buildMetadata($raw)->getPreRelease());
    }

    #[DataProvider('providePreReleaseCases')]
    public function testIsPreRelease(string $raw, null|string $expectedSuffix): void
    {
        $this->assertSame($expectedSuffix !== null, $this->buildMetadata($raw)->isPreRelease());
    }

    public static function providePreReleaseCases(): array
    {
        return [
            'v5.0.0 GA'              => ['v5.0.0',         null],
            'v6.0.0-ballot4 prerel'  => ['v6.0.0-ballot4', 'ballot4'],
        ];
    }

    // -------------------------------------------------------------------------
    // Cross-check: ballot version is R6 and pre-release, not any earlier range.
    // -------------------------------------------------------------------------

    public function testBallot4ClassifiesCorrectly(): void
    {
        $meta = $this->buildMetadata('v6.0.0-ballot4');

        $this->assertTrue($meta->isPreRelease(),  'v6.0.0-ballot4 must be flagged as pre-release');
        $this->assertSame('ballot4', $meta->getPreRelease());

        $this->assertFalse($meta->isDSTU1(), 'v6.0.0-ballot4 must not be DSTU1');
        $this->assertFalse($meta->isDSTU2(), 'v6.0.0-ballot4 must not be DSTU2');
        $this->assertFalse($meta->isSTU3(),  'v6.0.0-ballot4 must not be STU3');
        $this->assertFalse($meta->isR4(),    'v6.0.0-ballot4 must not be R4');
        $this->assertFalse($meta->isR4B(),   'v6.0.0-ballot4 must not be R4B');
        $this->assertFalse($meta->isR5(),    'v6.0.0-ballot4 must not be R5');
        $this->assertTrue($meta->isR6(),     'v6.0.0-ballot4 must be R6');
    }
}
