<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Utilities;

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

use DCarbone\PHPFHIR\Utilities\NameUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NameUtilsTest extends TestCase
{
    public static function sanitizeClassNameProvider(): array
    {
        return [
            'valid' => ['USCorePatient', '', 'USCorePatient'],
            'separators' => ['my-patient_profile', '', 'MyPatientProfile'],
            'dots and spaces' => ['us-core.patient name', '', 'UsCorePatientName'],
            'leading digit' => ['3d thing', 'Profile', 'Profile3dThing'],
        ];
    }

    #[DataProvider('sanitizeClassNameProvider')]
    public function testSanitizeClassName(string $name, string $digitPrefix, string $expected): void
    {
        $this->assertSame($expected, NameUtils::sanitizeClassName($name, $digitPrefix));
    }

    public static function invalidClassNameProvider(): array
    {
        return [
            'no alphanumerics' => ['---'],
            'leading digit without prefix' => ['3d thing'],
        ];
    }

    #[DataProvider('invalidClassNameProvider')]
    public function testSanitizeClassNameRejectsInvalid(string $name): void
    {
        $this->expectException(\DomainException::class);
        NameUtils::sanitizeClassName($name);
    }

    public function testToPascalCase(): void
    {
        $this->assertSame('UsCorePatient', NameUtils::toPascalCase('us-core.patient'));
        $this->assertSame('3dThing', NameUtils::toPascalCase('3d thing'));
        $this->assertSame('', NameUtils::toPascalCase('--'));
    }

    public static function splitFullyQualifiedNameProvider(): array
    {
        return [
            'namespaced' => ['Acme\\Gen\\Thing', ['Acme\\Gen', 'Thing']],
            'leading slash' => ['\\Acme\\Thing', ['Acme', 'Thing']],
            'global' => ['Thing', ['', 'Thing']],
            'global leading slash' => ['\\Thing', ['', 'Thing']],
        ];
    }

    #[DataProvider('splitFullyQualifiedNameProvider')]
    public function testSplitFullyQualifiedName(string $fqn, array $expected): void
    {
        $this->assertSame($expected, NameUtils::splitFullyQualifiedName($fqn));
    }

    public function testFieldNames(): void
    {
        $this->assertSame('FIELD_BIRTH_DATE', NameUtils::getFieldConstantName('birthDate'));
        $this->assertSame('getBirthDate', NameUtils::getGetterName('birthDate'));
    }
}
