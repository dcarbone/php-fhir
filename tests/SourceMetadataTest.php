<?php

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
        $rc->getProperty('_compiled')->setValue($instance, true);

        return $instance;
    }

    // -------------------------------------------------------------------------
    // getSemanticVersion
    // -------------------------------------------------------------------------

    /** @dataProvider provideSemanticVersionCases */
    public function testGetSemanticVersionUntrimmed(string $raw, string $expectedUntrimmed): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expectedUntrimmed, $meta->getSemanticVersion(false));
    }

    /** @dataProvider provideSemanticVersionCases */
    public function testGetSemanticVersionTrimmed(string $raw, string $expectedUntrimmed, string $expectedTrimmed): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expectedTrimmed, $meta->getSemanticVersion(true));
    }

    public static function provideSemanticVersionCases(): array
    {
        // [ raw stored value, expected untrimmed, expected trimmed ]
        return [
            'DSTU1'          => ['v0.0.82',         'v0.0.82',         '0.0.82'],
            'DSTU2'          => ['v1.0.2',           'v1.0.2',           '1.0.2'],
            'STU3'           => ['v3.0.2',           'v3.0.2',           '3.0.2'],
            'R4'             => ['v4.0.1',           'v4.0.1',           '4.0.1'],
            'R4B'            => ['v4.3.0',           'v4.3.0',           '4.3.0'],
            'R5'             => ['v5.0.0',           'v5.0.0',           '5.0.0'],
            'R6 ballot4'     => ['v6.0.0-ballot4',   'v6.0.0-ballot4',   '6.0.0-ballot4'],
        ];
    }

    // -------------------------------------------------------------------------
    // getShortVersion
    // NOTE: getShortVersion() currently calls  ltrim($this->_fhirVersion)  which
    // strips only whitespace, NOT the leading 'v'.  The correct call would be
    // ltrim($this->_fhirVersion, 'v').  Every version stored with a 'v' prefix
    // will therefore include that 'v' in the returned short version.
    // The expected values below reflect the CORRECT / INTENDED behaviour; the
    // tests for versions with a 'v' prefix will FAIL until the bug is fixed.
    // -------------------------------------------------------------------------

    /** @dataProvider provideShortVersionCases */
    public function testGetShortVersion(string $raw, string $expected): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expected, $meta->getShortVersion());
    }

    public static function provideShortVersionCases(): array
    {
        // [ raw stored value, expected short version (Major.Minor only) ]
        return [
            'DSTU1'      => ['v0.0.82',       '0.0'],   // FAILS: currently returns 'v0.0'
            'DSTU2'      => ['v1.0.2',         '1.0'],   // FAILS: currently returns 'v1.0'
            'STU3'       => ['v3.0.2',         '3.0'],   // FAILS: currently returns 'v3.0'
            'R4'         => ['v4.0.1',         '4.0'],   // FAILS: currently returns 'v4.0'
            'R4B'        => ['v4.3.0',         '4.3'],   // FAILS: currently returns 'v4.3'
            'R5'         => ['v5.0.0',         '5.0'],   // FAILS: currently returns 'v5.0'
            // v6 ballot: the '-ballot4' pre-release suffix appears after the patch
            // segment, so strrpos('.') still finds the second dot and the result
            // should be Major.Minor without any 'v' prefix or ballot tag.
            'R6 ballot4' => ['v6.0.0-ballot4', '6.0'],  // FAILS: currently returns 'v6.0'
        ];
    }

    // -------------------------------------------------------------------------
    // getVersionInteger
    // The format string "%'.-08s" effectively right-zero-pads the stripped digit
    // string to 8 characters (the '.' custom pad char is overridden by the '0'
    // flag in the width specifier, producing zero-fill).  intval() then reads
    // the full numeric value.
    //
    //   'v5.0.0'  -> strip -> '500'  -> right-pad to 8 -> '50000000' -> 50000000
    //   'v0.0.82' -> strip -> '0082' -> right-pad to 8 -> '00820000' -> 820000
    //
    // For 'v6.0.0-ballot4' the intermediate string is '600-ballot4' which is
    // already longer than 8 chars so no padding occurs.  intval() stops at the
    // '-' giving 600 – identical to a clean 'v6.0.0', so ballot/pre-release
    // versions are indistinguishable via getVersionInteger().
    // -------------------------------------------------------------------------

    /** @dataProvider provideVersionIntegerCases */
    public function testGetVersionInteger(string $raw, int $expected): void
    {
        $meta = $this->buildMetadata($raw);
        $this->assertSame($expected, $meta->getVersionInteger());
    }

    public static function provideVersionIntegerCases(): array
    {
        // [ raw stored value, expected integer ]
        return [
            'DSTU1'      => ['v0.0.82',        820000],
            'DSTU2'      => ['v1.0.2',         10200000],
            'STU3'       => ['v3.0.2',         30200000],
            'R4'         => ['v4.0.1',         40100000],
            'R4B'        => ['v4.3.0',         43000000],
            'R5'         => ['v5.0.0',         50000000],
            // '600-ballot4' is already >8 chars; no padding applied.
            // intval('600-ballot4') = 600 – same as clean v6.0.0.
            'R6 ballot4' => ['v6.0.0-ballot4', 600],
        ];
    }

    // -------------------------------------------------------------------------
    // is* range helpers
    // -------------------------------------------------------------------------

    /** @dataProvider provideIsDSTU1Cases */
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

    /** @dataProvider provideIsDSTU2Cases */
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

    /** @dataProvider provideIsSTU3Cases */
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

    /** @dataProvider provideIsR4Cases */
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

    /** @dataProvider provideIsR4BCases */
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

    /** @dataProvider provideIsR5Cases */
    public function testIsR5(string $raw, bool $expected): void
    {
        $this->assertSame($expected, $this->buildMetadata($raw)->isR5());
    }

    public static function provideIsR5Cases(): array
    {
        return [
            'v4.3.0 not R5' => ['v4.3.0', false],
            'v5.0.0 is R5'  => ['v5.0.0', true],
            'v5.0.1 is R5'  => ['v5.0.1', true],
        ];
    }

    // -------------------------------------------------------------------------
    // Ballot / pre-release version behaviour
    //
    // Composer Semver does NOT accept 'ballot4' as a valid pre-release
    // identifier ('ballot' is non-numeric and Semver only allows alphanumeric
    // dot-separated identifiers in a specific format).  As a result, ANY call
    // to an is*() method with 'v6.0.0-ballot4' throws UnexpectedValueException.
    //
    // Both tests below document that exception and will pass once (and only
    // once) the version string reaches an is*() call.  The real fix requires
    // either normalising the ballot suffix before passing it to Semver, or
    // adding a dedicated isBallot() / isR6Ballot() guard that matches before
    // the Semver range checks.
    // -------------------------------------------------------------------------

    public function testBallot4ThrowsOnDSTU1Check(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->buildMetadata('v6.0.0-ballot4')->isDSTU1();
    }

    public function testBallot4ThrowsOnIsR5Check(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->buildMetadata('v6.0.0-ballot4')->isR5();
    }
}
