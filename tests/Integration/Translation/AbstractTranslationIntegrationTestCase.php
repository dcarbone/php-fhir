<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Integration\Translation;

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

use DCarbone\PHPFHIRGenerated\Encoding\ResourceParser;
use DCarbone\PHPFHIRGenerated\Translation\TranslationNotice;
use DCarbone\PHPFHIRGenerated\Translation\TranslationResult;
use DCarbone\PHPFHIRGenerated\Types\ResourceTypeInterface;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Version as DSTU1Version;
use DCarbone\PHPFHIRGenerated\Versions\DSTU2\Version as DSTU2Version;
use DCarbone\PHPFHIRGenerated\Versions\R4\Version as R4Version;
use DCarbone\PHPFHIRGenerated\Versions\R4B\Version as R4BVersion;
use DCarbone\PHPFHIRGenerated\Versions\R5\Version as R5Version;
use DCarbone\PHPFHIRGenerated\Versions\STU3\Version as STU3Version;
use DCarbone\PHPFHIRGenerated\Versions\VersionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Base for integration tests that exercise translation against real, generated FHIR version code.
 */
abstract class AbstractTranslationIntegrationTestCase extends TestCase
{
    protected const R4_PATIENT_JSON = '{"resourceType":"Patient","id":"p1","active":true,"name":[{"family":"Doe","given":["Jane"]}],"gender":"female","birthDate":"1970-01-02"}';

    /** @var array<string, \DCarbone\PHPFHIRGenerated\Versions\VersionInterface> */
    private static array $_versions = [];

    protected static function version(string $name): VersionInterface
    {
        if ([] === self::$_versions) {
            self::$_versions = [
                'DSTU1' => new DSTU1Version(),
                'DSTU2' => new DSTU2Version(),
                'STU3' => new STU3Version(),
                'R4' => new R4Version(),
                'R4B' => new R4BVersion(),
                'R5' => new R5Version(),
            ];
        }
        return self::$_versions[$name];
    }

    protected static function parse(string $versionName, string $json): ResourceTypeInterface
    {
        $resource = ResourceParser::parseJSON(self::version($versionName), $json);
        self::assertNotNull($resource);
        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function decode(ResourceTypeInterface $resource): array
    {
        return json_decode(json_encode($resource, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, string>
     */
    protected static function noticePaths(TranslationResult $result): array
    {
        return array_map(
            static fn(TranslationNotice $notice): string => sprintf(
                '%s %s->%s',
                $notice->getPath(),
                $notice->getSourceVersion(),
                $notice->getTargetVersion(),
            ),
            $result->getNotices(),
        );
    }

    protected static function assertResourceVersion(string $versionName, ResourceTypeInterface $resource): void
    {
        self::assertSame(self::version($versionName)->getName(), $resource->_getFHIRVersion()->getName());
    }
}
