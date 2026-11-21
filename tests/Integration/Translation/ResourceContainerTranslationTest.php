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

use DCarbone\PHPFHIRGenerated\Translation\FieldMetadataFactory;
use DCarbone\PHPFHIRGenerated\Translation\Translator;
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRBundle as R4Bundle;
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIRPatient as R4Patient;
use DCarbone\PHPFHIRGenerated\Versions\R4\VersionContainedTypeInterface as R4ContainedTypeInterface;
use DCarbone\PHPFHIRGenerated\Versions\R5\Types\FHIRBase\FHIRResource\FHIRBundle as R5Bundle;
use DCarbone\PHPFHIRGenerated\Versions\R5\Types\FHIRBase\FHIRResource\FHIRDomainResource\FHIRPatient as R5Patient;
use DCarbone\PHPFHIRGenerated\Versions\R5\VersionContainedTypeInterface as R5ContainedTypeInterface;

/**
 * Translates resources held in resource-container fields: Bundle.entry.resource and DomainResource.contained.
 */
class ResourceContainerTranslationTest extends AbstractTranslationIntegrationTestCase
{
    private const R4_BUNDLE_JSON = '{"resourceType":"Bundle","id":"b1","type":"collection","entry":[{"fullUrl":"urn:uuid:p1","resource":{"resourceType":"Patient","id":"p1","active":true,"gender":"female"}},{"fullUrl":"urn:uuid:p2","resource":{"resourceType":"Patient","id":"p2","gender":"male"}}]}';
    private const R4_CONTAINED_JSON = '{"resourceType":"Patient","id":"p1","contained":[{"resourceType":"Patient","id":"c1","gender":"male"},{"resourceType":"Patient","id":"c2","active":false}],"active":true}';

    public function testMetadataDescribesTheContainedResourceInterface(): void
    {
        $factory = new FieldMetadataFactory();

        $entry = self::parse('R4', self::R4_BUNDLE_JSON)->getEntry()[0];
        self::assertSame(
            R4ContainedTypeInterface::class,
            $factory->forType($entry)['resource']->getType(),
        );
        self::assertSame(
            R4ContainedTypeInterface::class,
            $factory->forType(new R4Patient())['contained']->getType(),
        );
    }

    public function testBundleEntryResourcesTranslateBothWays(): void
    {
        $translator = new Translator();

        $forward = $translator->translate(self::parse('R4', self::R4_BUNDLE_JSON), self::version('R5'));
        $r5 = $forward->getResource();
        self::assertInstanceOf(R5Bundle::class, $r5);
        self::assertSame([], $forward->getNotices());
        foreach ($r5->getEntry() as $entry) {
            self::assertInstanceOf(R5Patient::class, $entry->getResource());
            self::assertInstanceOf(R5ContainedTypeInterface::class, $entry->getResource());
        }
        self::assertEquals(json_decode(self::R4_BUNDLE_JSON, true), self::decode($r5));

        $backward = $translator->translate($r5, self::version('R4'));
        self::assertInstanceOf(R4Bundle::class, $backward->getResource());
        self::assertSame([], $backward->getNotices());
        self::assertEquals(json_decode(self::R4_BUNDLE_JSON, true), self::decode($backward->getResource()));
    }

    public function testContainedResourcesTranslateBothWays(): void
    {
        $translator = new Translator();

        $forward = $translator->translate(self::parse('R4', self::R4_CONTAINED_JSON), self::version('R5'));
        $r5 = $forward->getResource();
        self::assertInstanceOf(R5Patient::class, $r5);
        self::assertSame([], $forward->getNotices());
        self::assertCount(2, $r5->getContained());
        foreach ($r5->getContained() as $contained) {
            self::assertInstanceOf(R5Patient::class, $contained);
        }
        self::assertEquals(json_decode(self::R4_CONTAINED_JSON, true), self::decode($r5));

        $backward = $translator->translate($r5, self::version('R4'));
        self::assertInstanceOf(R4Patient::class, $backward->getResource());
        self::assertSame([], $backward->getNotices());
        self::assertEquals(json_decode(self::R4_CONTAINED_JSON, true), self::decode($backward->getResource()));
    }
}
