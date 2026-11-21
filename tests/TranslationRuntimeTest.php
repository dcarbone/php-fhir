<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests;

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

use DCarbone\PHPFHIR\Builder;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Translation\HopMapDefinition;
use DCarbone\PHPFHIR\Translation\XverExtensionDefinition;
use DCarbone\PHPFHIR\Version\Definition;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureStatusEnum;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use TranslationRuntimeGenerated\Translation\FieldMetadata;
use TranslationRuntimeGenerated\Translation\FieldMetadataFactory;
use TranslationRuntimeGenerated\Translation\FieldMetadataFactoryInterface;
use TranslationRuntimeGenerated\Translation\CodeMap;
use TranslationRuntimeGenerated\Translation\CodeMapResult;
use TranslationRuntimeGenerated\Translation\GenericTypeTranslator;
use TranslationRuntimeGenerated\Translation\HopMapInterface;
use TranslationRuntimeGenerated\Translation\Maps\HopMap_RenderedR4ToR5;
use TranslationRuntimeGenerated\Translation\Maps\HopMap_RenderedR5ToR4;
use TranslationRuntimeGenerated\Translation\MissingXverDefinitionException;
use TranslationRuntimeGenerated\Translation\OnUnmappableFieldEnum;
use TranslationRuntimeGenerated\Translation\TranslationConfig;
use TranslationRuntimeGenerated\Translation\TranslationContext;
use TranslationRuntimeGenerated\Translation\TranslationNoticeCollector;
use TranslationRuntimeGenerated\Translation\TranslationNotice;
use TranslationRuntimeGenerated\Translation\TranslationResult;
use TranslationRuntimeGenerated\Translation\Translator;
use TranslationRuntimeGenerated\Translation\TypeTranslatorDispatcher;
use TranslationRuntimeGenerated\Translation\TypeTranslatorInterface;
use TranslationRuntimeGenerated\Translation\UnmappableFieldException;
use TranslationRuntimeGenerated\Translation\XverExtension;
use TranslationRuntimeGenerated\Translation\XverExtensionEmitter;
use TranslationRuntimeGenerated\Types\TypeInterface;
use TranslationRuntimeGenerated\Versions\R4\Types\Balance as R4Balance;
use TranslationRuntimeGenerated\Versions\R4\Types\BalanceDetail as R4BalanceDetail;
use TranslationRuntimeGenerated\Versions\R4\Types\Leaf as R4Leaf;
use TranslationRuntimeGenerated\Versions\R4\Types\Name as R4Name;
use TranslationRuntimeGenerated\Versions\R4\Types\Patient as R4Patient;
use TranslationRuntimeGenerated\Versions\R4\Types\Coding as R4Coding;
use TranslationRuntimeGenerated\Versions\R4\Types\CodeableConcept as R4CodeableConcept;
use TranslationRuntimeGenerated\Versions\R4\Types\Status as R4Status;
use TranslationRuntimeGenerated\Versions\R4\Version as R4Version;
use TranslationRuntimeGenerated\Versions\R3\Types\Leaf as R3Leaf;
use TranslationRuntimeGenerated\Versions\R3\Types\Patient as R3Patient;
use TranslationRuntimeGenerated\Versions\R3\Types\Coding as R3Coding;
use TranslationRuntimeGenerated\Versions\R3\Types\CodeableConcept as R3CodeableConcept;
use TranslationRuntimeGenerated\Versions\R4B\Types\Leaf as R4BLeaf;
use TranslationRuntimeGenerated\Versions\R4B\Types\Name as R4BName;
use TranslationRuntimeGenerated\Versions\R4B\Types\Patient as R4BPatient;
use TranslationRuntimeGenerated\Versions\R4B\Version as R4BVersion;
use TranslationRuntimeGenerated\Versions\R5\Types\Leaf as R5Leaf;
use TranslationRuntimeGenerated\Versions\R5\Types\Name as R5Name;
use TranslationRuntimeGenerated\Versions\R5\Types\Patient as R5Patient;
use TranslationRuntimeGenerated\Versions\R5\Types\Coding as R5Coding;
use TranslationRuntimeGenerated\Versions\R5\Types\CodeableConcept as R5CodeableConcept;
use TranslationRuntimeGenerated\Versions\R5\Types\Extension as R5Extension;
use TranslationRuntimeGenerated\Versions\R5\Version as R5Version;
use TranslationRuntimeGenerated\Versions\R6\Types\Coding as R6Coding;
use TranslationRuntimeGenerated\Versions\R6\Types\Patient as R6Patient;
use TranslationRuntimeGenerated\Versions\R6\Version as R6Version;
use TranslationRuntimeGenerated\Versions\VersionInterface;

class TranslationRuntimeTest extends TestCase
{
    private static TemporaryDirectory $_tmpdir;
    private static string $_autoloader;

    public static function setUpBeforeClass(): void
    {
        self::$_tmpdir = new TemporaryDirectory(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'TranslationRuntimeTest-' . bin2hex(random_bytes(8)),
        );
        self::$_tmpdir->create();
        $directory = self::$_tmpdir->path();
        $schemaPaths = [];
        foreach ([
            'R3' => 'v3.0.2',
            'R4' => 'v4.0.1',
            'R4B' => 'v4.3.0',
            'R5' => 'v5.0.0',
            'R6' => 'v6.0.0',
        ] as $versionName => $semanticVersion) {
            $schemaPaths[$versionName] = $directory . '/schema-' . strtolower($versionName);
            if (!is_dir($schemaPaths[$versionName])) {
                mkdir($schemaPaths[$versionName], 0777, true);
            }
            file_put_contents(
                $schemaPaths[$versionName] . '/fhir-base.xsd',
                sprintf("<!--\nGenerated on 2022-01-01 for FHIR %s\n-->\n", $semanticVersion),
            );
        }
        $packagePath = $directory . '/xver-package/package';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . '/package.json', json_encode([
            'name' => 'example.xver',
            'version' => '1.0.0',
        ], JSON_THROW_ON_ERROR));
        file_put_contents($packagePath . '/ConceptMap-example.json', json_encode([
            'resourceType' => 'ConceptMap',
            'id' => 'example',
            'group' => [[
                'source' => 'http://example.org/source',
                'target' => 'http://example.org/target',
                'element' => [[
                    'code' => 'legacy',
                    'target' => [['code' => 'current', 'relationship' => 'equivalent']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));
        $config = Config::fromArray([
            'libraryPath' => $directory,
            'libraryNamespacePrefix' => 'TranslationRuntimeGenerated',
            'versions' => [
                ['name' => 'R3', 'schemaPath' => $schemaPaths['R3']],
                ['name' => 'R4', 'schemaPath' => $schemaPaths['R4']],
                ['name' => 'R4B', 'schemaPath' => $schemaPaths['R4B']],
                ['name' => 'R5', 'schemaPath' => $schemaPaths['R5']],
                ['name' => 'R6', 'schemaPath' => $schemaPaths['R6']],
            ],
            'translationPackages' => [[
                'sourceVersion' => 'R3',
                'targetVersion' => 'R6',
                'source' => $directory . '/xver-package',
            ]],
        ]);
        $builder = new Builder($config);
        foreach (['R3', 'R4', 'R4B', 'R5', 'R6'] as $versionName) {
            $version = $config->getVersion($versionName);
            (new \ReflectionProperty(Definition::class, '_types'))
                ->setValue($version->getDefinition(), new Types($version));
        }
        $builder->writeLibraryCoreEntities();
        $builder->writeLibraryTranslationMaps();
        $autoloader = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_CLASSNAME_AUTOLOADER);
        self::$_autoloader = $autoloader->getFullyQualifiedName(false);
        require $autoloader->getFilepath();
        require_once __DIR__ . '/Fixtures/TranslationRuntimeFixtures.php';
    }

    public static function tearDownAfterClass(): void
    {
        $autoloader = self::$_autoloader;
        $autoloader::unregister();
        self::$_tmpdir->delete();
    }

    public function testNestedTranslatorsAndNoticesAcrossHopsAreIsolatedPerCall(): void
    {
        $first = new class implements TypeTranslatorInterface {
            public array $paths = [];

            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                if (!$source instanceof R4Leaf) {
                    throw new \InvalidArgumentException('Expected an R4 leaf.');
                }
                $this->paths[] = $context->getPath();
                $context->addNotice(new TranslationNotice(
                    $context->getPath(),
                    'custom',
                    $context->getSourceVersion()->getName(),
                    $context->getTargetVersion()->getName(),
                    'First hop',
                ));
                return new R4BLeaf($source->getValue() . '-r4b');
            }
        };
        $second = new class implements TypeTranslatorInterface {
            public array $paths = [];

            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                if (!$source instanceof R4BLeaf) {
                    throw new \InvalidArgumentException('Expected an R4B leaf.');
                }
                $this->paths[] = $context->getPath();
                $context->addNotice(new TranslationNotice(
                    $context->getPath(),
                    'custom',
                    $context->getSourceVersion()->getName(),
                    $context->getTargetVersion()->getName(),
                    'Second hop',
                ));
                return new R5Leaf($source->getValue() . '-r5');
            }
        };

        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));
        $translator->register(new R4Version(), new R4BVersion(), 'string-primitive', $first);
        $translator->register(new R4BVersion(), new R5Version(), 'string-primitive', $second);
        $name = (new R4Name())->setFamily(new R4Leaf('family'));
        $name->setLegacy(new R4Leaf('removed'));
        $source = (new R4Patient())->setName($name);

        $result = $translator->translate($source, new R5Version());
        $patient = $result->getResource();
        self::assertInstanceOf(R5Patient::class, $patient);
        self::assertNotNull($patient->getName());
        self::assertNotNull($patient->getName()->getFamily());
        self::assertSame('family-r4b-r5', $patient->getName()->getFamily()->getValue());
        self::assertSame('family', $source->getName()->getFamily()->getValue());
        self::assertSame(['Patient.name.family'], $first->paths);
        self::assertSame(['Patient.name.family'], $second->paths);

        $notices = [];
        foreach ($result->getNotices() as $notice) {
            $notices[] = [$notice->getKind(), $notice->getPath(), $notice->getSourceVersion(), $notice->getTargetVersion()];
        }
        self::assertEqualsCanonicalizing([
            ['dropped', 'Patient.name.legacy', 'R4', 'R4B'],
            ['custom', 'Patient.name.family', 'R4', 'R4B'],
            ['custom', 'Patient.name.family', 'R4B', 'R5'],
        ], $notices);

        $empty = $translator->translate(new R4Patient(), new R5Version());
        self::assertSame([], $empty->getNotices());
        self::assertCount(3, $result->getNotices());

        $sameVersion = $translator->translate($source, new R4Version());
        self::assertSame($source, $sameVersion->getResource());
        self::assertSame([], $sameVersion->getNotices());
    }

    public function testDefaultTranslationRunsBackwardsAcrossHops(): void
    {
        $name = (new R5Name())->setFamily(new R5Leaf('family'))
            ->setGiven(new R5Leaf('first'), new R5Leaf('second'));
        $source = (new R5Patient())->setName($name);
        $result = (new Translator())->translate($source, new R4Version());

        self::assertInstanceOf(R4Patient::class, $result->getResource());
        self::assertSame('family', $result->getResource()->getName()->getFamily()->getValue());
        self::assertSame(
            ['first', 'second'],
            array_map(
                static fn(R4Leaf $leaf): null|string => $leaf->getValue(),
                $result->getResource()->getName()->getGiven(),
            ),
        );
        self::assertSame([], $result->getNotices());
    }

    public function testCodeMapsTranslateCodingInsideCodeableConceptAndPreserveUnmappedValuesByPolicy(): void
    {
        $hopMap = $this->codeMapHop(['legacy' => 'current']);
        $genericTranslator = new GenericTypeTranslator();
        $source = (new R4CodeableConcept())->setCoding(
            (new R4Coding())
                ->setSystem(new R4Leaf('http://example.org/source'))
                ->setCode(new R4Leaf('legacy')),
        );
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'CodeableConcept',
            $genericTranslator,
            new TranslationNoticeCollector(),
            $hopMap,
        );

        $translated = $context->translate($source);
        self::assertInstanceOf(R5CodeableConcept::class, $translated);
        self::assertCount(1, $translated->getCoding());
        self::assertInstanceOf(R5Coding::class, $translated->getCoding()[0]);
        self::assertSame('http://example.org/target', $translated->getCoding()[0]->getSystem()->getValue());
        self::assertSame('current', $translated->getCoding()[0]->getCode()->getValue());
        self::assertSame('legacy', $source->getCoding()[0]->getCode()->getValue());

        $unmappedSource = (new R4Coding())
            ->setSystem(new R4Leaf('http://example.org/source'))
            ->setCode(new R4Leaf('unlisted'));
        $unmappedNotices = new TranslationNoticeCollector();
        $unmappedContext = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Coding',
            $genericTranslator,
            $unmappedNotices,
            $hopMap,
        );
        $unmapped = $unmappedContext->translate($unmappedSource);
        self::assertNull($unmapped);
        self::assertCount(2, $unmappedNotices->getNotices());

        $noMapContext = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Coding',
            $genericTranslator,
            new TranslationNoticeCollector(),
        );
        $unchanged = $noMapContext->translate($unmappedSource);
        self::assertInstanceOf(R5Coding::class, $unchanged);
        self::assertSame('http://example.org/source', $unchanged->getSystem()->getValue());
        self::assertSame('unlisted', $unchanged->getCode()->getValue());

        $bareCode = $noMapContext->translate(new R4Leaf('legacy'));
        self::assertInstanceOf(R5Leaf::class, $bareCode);
        self::assertSame('legacy', $bareCode->getValue());
    }

    public function testCodingVersionIsUnmappableWhenCodeMapChangesSystem(): void
    {
        $source = (new R4Coding())
            ->setSystem(new R4Leaf('http://example.org/source'))
            ->setCode(new R4Leaf('legacy'))
            ->setVersion(new R4Leaf('source-version'));
        $notices = new TranslationNoticeCollector();
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Coding',
            new GenericTypeTranslator(),
            $notices,
            $this->codeMapHop(['legacy' => 'current']),
        );

        $translated = $context->translate($source);

        self::assertInstanceOf(R5Coding::class, $translated);
        self::assertSame('http://example.org/target', $translated->getSystem()->getValue());
        self::assertSame('current', $translated->getCode()->getValue());
        self::assertNull($translated->getVersion());
        self::assertSame(['Coding.version'], array_map(
            static fn(TranslationNotice $notice): string => $notice->getPath(),
            $notices->getNotices(),
        ));
    }

    public function testDateTimeWithTimePrecisionCannotBeCoercedToDate(): void
    {
        $source = new class('1970-01-02') extends FixtureLeaf {
            public function _getFHIRVersion(): \TranslationRuntimeGenerated\FHIRVersion
            {
                return R4Version::getFHIRVersion();
            }

            public function _getFHIRTypeName(): string
            {
                return 'dateTime-primitive';
            }
        };
        $timestamp = new class('1970-01-02T12:34:56Z') extends FixtureLeaf {
            public function _getFHIRVersion(): \TranslationRuntimeGenerated\FHIRVersion
            {
                return R4Version::getFHIRVersion();
            }

            public function _getFHIRTypeName(): string
            {
                return 'dateTime-primitive';
            }
        };
        $target = new class extends FixtureLeaf {
            public function _getFHIRVersion(): \TranslationRuntimeGenerated\FHIRVersion
            {
                return R5Version::getFHIRVersion();
            }

            public function _getFHIRTypeName(): string
            {
                return 'date-primitive';
            }
        };
        $notices = new TranslationNoticeCollector();
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Patient.birthDate',
            new GenericTypeTranslator(),
            $notices,
        );

        $translatedDate = $context->translateField('value', $source, $target::class);
        $unmappableTimestamp = $context->translateField('value', $timestamp, $target::class);

        self::assertInstanceOf($target::class, $translatedDate);
        self::assertSame('1970-01-02', $translatedDate->getValue());
        self::assertNull($unmappableTimestamp);
        self::assertSame(['Patient.birthDate.value'], array_map(
            static fn(TranslationNotice $notice): string => $notice->getPath(),
            $notices->getNotices(),
        ));

        $throwingContext = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::THROW),
            'Patient.birthDate',
            new GenericTypeTranslator(),
            new TranslationNoticeCollector(),
        );
        $this->expectException(UnmappableFieldException::class);
        $throwingContext->translateField('value', $timestamp, $target::class);
    }

    public function testUnmappedConceptMapCodeUsesThrowPolicy(): void
    {
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::THROW),
            'Coding',
            new GenericTypeTranslator(),
            new TranslationNoticeCollector(),
            $this->codeMapHop(['mapped' => 'target']),
        );
        $source = (new R4Coding())
            ->setSystem(new R4Leaf('http://example.org/source'))
            ->setCode(new R4Leaf('unlisted'));

        $this->expectException(UnmappableFieldException::class);
        $context->translate($source);
    }

    public function testConfiguredNonAdjacentCodeMapUsesItsDirectHop(): void
    {
        $source = (new R3Patient())->setConcept(
            (new R3CodeableConcept())->setCoding(
                (new R3Coding())
                    ->setSystem(new R3Leaf('http://example.org/source'))
                    ->setCode(new R3Leaf('legacy')),
            ),
        );

        $result = (new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW)))
            ->translate($source, new R6Version());
        self::assertInstanceOf(R6Patient::class, $result->getResource());
        self::assertInstanceOf(R6Coding::class, $result->getResource()->getConcept()->getCoding()[0]);
        self::assertSame(
            'http://example.org/target',
            $result->getResource()->getConcept()->getCoding()[0]->getSystem()->getValue(),
        );
        self::assertSame('current', $result->getResource()->getConcept()->getCoding()[0]->getCode()->getValue());
        self::assertSame([], $result->getNotices());
    }

    private function codeMapHop(array $codeMappings): HopMapInterface
    {
        return new class(new CodeMap(
            'http://example.org/source',
            'http://example.org/target',
            $codeMappings,
        )) implements HopMapInterface {
            public function __construct(private readonly CodeMap $codeMap)
            {
            }

            public function getSourceVersion(): R4Version
            {
                return new R4Version();
            }

            public function getTargetVersion(): R5Version
            {
                return new R5Version();
            }

            public function getRemovedTypes(): array
            {
                return [];
            }

            public function getCodeMaps(): array
            {
                return [$this->codeMap];
            }

            public function getXverExtensions(): array
            {
                return [];
            }
        };
    }

    public function testFieldMetadataFactoryUsesCachedTypedDescriptors(): void
    {
        $factory = new FieldMetadataFactory();
        self::assertInstanceOf(FieldMetadataFactoryInterface::class, $factory);
        $fields = $factory->forType(new R4Name());
        self::assertArrayHasKey('family', $fields);
        self::assertArrayHasKey('given', $fields);
        self::assertArrayHasKey('legacy', $fields);
        self::assertInstanceOf(FieldMetadata::class, $fields['family']);
        self::assertSame(FixtureLeaf::class, $fields['family']->getType());
        self::assertFalse($fields['family']->isCollection());
        self::assertSame('getFamily', $fields['family']->getGetter());
        self::assertSame('setFamily', $fields['family']->getSetter());
        self::assertTrue($fields['given']->isCollection());
        self::assertSame('getGiven', $fields['given']->getGetter());
        self::assertSame('setGiven', $fields['given']->getSetter());
        self::assertSame($fields['given'], $factory->forType(new R4Name())['given']);
        self::assertNotSame($fields['given'], (new FieldMetadataFactory())->forType(new R4Name())['given']);
        self::assertSame(FixtureName::class, $factory->forType(new R4Patient())['name']->getType());
    }

    public function testFieldMetadataFactoryRejectsNonFhirTypes(): void
    {
        $this->expectException(\TypeError::class);
        (new FieldMetadataFactory())->forType(new \stdClass());
    }

    public function testFieldMetadataFactoryRejectsClassNames(): void
    {
        $this->expectException(\TypeError::class);
        (new FieldMetadataFactory())->forType(R4Name::class);
    }

    public function testInjectedMetadataFactoryControlsEveryHopAndNestedTranslation(): void
    {
        $factory = new class implements FieldMetadataFactoryInterface {
            /** @var class-string<TypeInterface>[] */
            public array $classes = [];

            /** @var TypeInterface[] */
            public array $types = [];

            public function __construct(private readonly FieldMetadataFactory $default = new FieldMetadataFactory())
            {
            }

            public function forType(TypeInterface $type): array
            {
                $this->types[] = $type;
                $className = $type::class;
                $this->classes[] = $className;
                $fields = $this->default->forType($type);
                if (R4Name::class === $className) {
                    unset($fields['legacy']);
                    $fields['family'] = new FieldMetadata(FixtureLeaf::class, false, 'getLegacy', 'setLegacy');
                }
                return $fields;
            }
        };
        $translator = new Translator(metadataFactory: $factory);
        $name = (new R4Name())->setFamily(new R4Leaf('original'))->setLegacy(new R4Leaf('remapped'));
        $name->setGiven(new R4Leaf('first'), new R4Leaf('second'));
        $source = (new R4Patient())->setName($name);

        $result = $translator->translate($source, new R5Version());
        self::assertSame('remapped', $result->getResource()->getName()->getFamily()->getValue());
        self::assertCount(2, $result->getResource()->getName()->getGiven());
        self::assertSame([], $result->getNotices());
        self::assertSame($source, $factory->types[0]);
        self::assertContains($name, $factory->types);
        self::assertContains($result->getResource(), $factory->types);
        self::assertContains($result->getResource()->getName(), $factory->types);
        self::assertEqualsCanonicalizing(
            [R4Patient::class, R4BPatient::class, R5Patient::class, R4Name::class, R4BName::class, R5Name::class],
            array_values(array_unique($factory->classes)),
        );

        $reverse = $translator->translate($result->getResource(), new R4Version());
        self::assertSame('remapped', $reverse->getResource()->getName()->getLegacy()->getValue());
        self::assertNull($reverse->getResource()->getName()->getFamily());
        self::assertSame([], $reverse->getNotices());

        $factory->classes = [];
        $translator->register(new R4Version(), new R5Version(), 'Patient', new GenericTypeTranslator($factory));
        $direct = $translator->translate($source, new R5Version());
        self::assertSame('remapped', $direct->getResource()->getName()->getFamily()->getValue());
        self::assertEqualsCanonicalizing(
            [R4Patient::class, R5Patient::class, R4Name::class, R5Name::class],
            $factory->classes,
        );

        $defaultTranslator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));
        $defaultResult = $defaultTranslator->translate($source, new R5Version());
        self::assertSame('original', $defaultResult->getResource()->getName()->getFamily()->getValue());
        self::assertSame('original', $source->getName()->getFamily()->getValue());
    }

    public function testInjectedMetadataFactoryExceptionsPropagate(): void
    {
        $factory = new class implements FieldMetadataFactoryInterface {
            public function forType(TypeInterface $type): array
            {
                throw new \RuntimeException('Metadata discovery failed.');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Metadata discovery failed.');
        (new Translator(metadataFactory: $factory))->translate(new R4Patient(), new R5Version());
    }

    public function testHopMapRemovedTypeUsesTheConfiguredUnmappablePolicy(): void
    {
        $sourceVersion = new R4Version();
        $targetVersion = new R5Version();
        $map = new class($sourceVersion, $targetVersion) implements HopMapInterface {
            public function __construct(
                private readonly R4Version $sourceVersion,
                private readonly R5Version $targetVersion,
            ) {
            }

            public function getSourceVersion(): VersionInterface
            {
                return $this->sourceVersion;
            }

            public function getTargetVersion(): VersionInterface
            {
                return $this->targetVersion;
            }

            public function getRemovedTypes(): array
            {
                return ['Patient'];
            }

            public function getCodeMaps(): array
            {
                return [];
            }

            public function getXverExtensions(): array
            {
                return [];
            }
        };
        $notices = new TranslationNoticeCollector();
        $dispatcher = new TypeTranslatorDispatcher();
        $context = new TranslationContext(
            $sourceVersion,
            $targetVersion,
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Patient',
            $dispatcher,
            $notices,
            $map,
        );

        self::assertNull($context->translate(new R4Patient()));
        self::assertCount(1, $notices->getNotices());
        self::assertSame('Patient', $notices->getNotices()[0]->getPath());
        self::assertSame('dropped', $notices->getNotices()[0]->getKind());
    }

    public function testRenderedHopMapsPreserveDefinitionsAndApplyRemovedTypePolicy(): void
    {
        $directory = self::$_tmpdir->path();
        $config = Config::fromArray([
            'libraryPath' => $directory,
            'libraryNamespacePrefix' => 'TranslationRuntimeGenerated',
            'versions' => [
                ['name' => 'R4', 'schemaPath' => $directory],
                ['name' => 'R5', 'schemaPath' => $directory],
            ],
        ]);
        $r4 = $config->getVersion('R4');
        $r5 = $config->getVersion('R5');
        $definition = new HopMapDefinition(
            removedTypes: ['HumanName', "old'\\Type"],
            xverExtensions: [
                'Account.currency' => new XverExtensionDefinition(
                    url: 'http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency',
                    field: 'currency',
                    valueTypes: ['CodeableConcept'],
                ),
                'Account.diagnosis' => new XverExtensionDefinition(
                    url: 'http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.diagnosis',
                    field: 'diagnosis',
                    modifier: true,
                    children: [
                        new XverExtensionDefinition(url: 'condition', field: 'condition', valueTypes: ['Reference']),
                        new XverExtensionDefinition(
                            url: "it's",
                            field: 'dateOfDiagnosis',
                            valueTypes: ['dateTime'],
                            datatype: 'CodeableReference',
                        ),
                    ],
                ),
            ],
        );

        $forwardPath = self::$_tmpdir->path('HopMap_R4ToR5.php');
        $reversePath = self::$_tmpdir->path('HopMap_R5ToR4.php');
        self::assertNotFalse(file_put_contents(
            $forwardPath,
            Templates::renderTranslationHopMap($r4, $r5, $definition, 'HopMap_RenderedR4ToR5'),
        ));
        self::assertNotFalse(file_put_contents(
            $reversePath,
            Templates::renderTranslationHopMap($r5, $r4, new HopMapDefinition([], []), 'HopMap_RenderedR5ToR4'),
        ));
        require $forwardPath;
        require $reversePath;

        $forward = new HopMap_RenderedR4ToR5();
        self::assertInstanceOf(HopMapInterface::class, $forward);
        self::assertInstanceOf(R4Version::class, $forward->getSourceVersion());
        self::assertInstanceOf(R5Version::class, $forward->getTargetVersion());
        self::assertSame($definition->getRemovedTypes(), $forward->getRemovedTypes());

        $reverse = new HopMap_RenderedR5ToR4();
        self::assertInstanceOf(R5Version::class, $reverse->getSourceVersion());
        self::assertInstanceOf(R4Version::class, $reverse->getTargetVersion());
        self::assertSame([], $reverse->getRemovedTypes());
        self::assertSame([], $reverse->getXverExtensions());

        $extensions = $forward->getXverExtensions();
        self::assertSame(['Account.currency', 'Account.diagnosis'], array_keys($extensions));
        self::assertContainsOnlyInstancesOf(XverExtension::class, $extensions);
        $currency = $extensions['Account.currency'];
        self::assertSame('http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency', $currency->getUrl());
        self::assertSame('currency', $currency->getField());
        self::assertFalse($currency->isModifier());
        self::assertFalse($currency->isComplex());
        self::assertSame(['CodeableConcept'], $currency->getValueTypes());
        self::assertNull($currency->getDatatype());
        $diagnosis = $extensions['Account.diagnosis'];
        self::assertTrue($diagnosis->isModifier());
        self::assertTrue($diagnosis->isComplex());
        self::assertSame([], $diagnosis->getValueTypes());
        self::assertCount(2, $diagnosis->getChildren());
        self::assertSame('condition', $diagnosis->getChildren()[0]->getUrl());
        self::assertSame(['Reference'], $diagnosis->getChildren()[0]->getValueTypes());
        self::assertSame("it's", $diagnosis->getChildren()[1]->getUrl());
        self::assertSame('dateOfDiagnosis', $diagnosis->getChildren()[1]->getField());
        self::assertSame('CodeableReference', $diagnosis->getChildren()[1]->getDatatype());

        $notices = new TranslationNoticeCollector();
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Patient',
            new TypeTranslatorDispatcher(),
            $notices,
            $forward,
        );
        $source = (new R4Patient())->setName((new R4Name())->setFamily(new R4Leaf('family')));
        $translated = $context->translate($source);
        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertNull($translated->getName());
        self::assertCount(1, $notices->getNotices());
        self::assertSame('Patient.name', $notices->getNotices()[0]->getPath());
        self::assertSame('dropped', $notices->getNotices()[0]->getKind());
    }

    public function testFieldMetadataOnlyDescribesAField(): void
    {
        $field = new FieldMetadata(FixtureLeaf::class, true, 'getGiven', 'setGiven');

        self::assertSame(FixtureLeaf::class, $field->getType());
        self::assertTrue($field->isCollection());
        self::assertSame('getGiven', $field->getGetter());
        self::assertSame('setGiven', $field->getSetter());
        self::assertFalse(method_exists(FieldMetadata::class, 'forType'));
        self::assertSame(
            [],
            (new \ReflectionClass(FieldMetadata::class))->getProperties(\ReflectionProperty::IS_STATIC),
        );
    }

    public function testDirectRegisteredWrapperCanDelegateToDefaultTranslator(): void
    {
        $wrapper = new class(new GenericTypeTranslator()) implements TypeTranslatorInterface {
            public array $paths = [];

            public function __construct(private readonly TypeTranslatorInterface $default)
            {
            }

            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                $this->paths[] = $context->getPath();
                $context->addNotice(new TranslationNotice($context->getPath(), 'custom', 'R4', 'R5', 'Wrapped'));
                return $this->default->translate($source, $context);
            }
        };
        $translator = new Translator();
        $translator->register(new R4Version(), new R5Version(), 'Patient', $wrapper);
        $source = (new R4Patient())->setName((new R4Name())->setFamily(new R4Leaf('family')));

        $result = $translator->translate($source, new R5Version());
        self::assertInstanceOf(R5Patient::class, $result->getResource());
        self::assertSame('family', $result->getResource()->getName()->getFamily()->getValue());
        self::assertSame(['Patient'], $wrapper->paths);
        self::assertCount(1, $result->getNotices());
        self::assertSame('Wrapped', $result->getNotices()[0]->getMessage());
    }

    public function testNestedNullUsesTheConfiguredPolicyAndFullPath(): void
    {
        $missing = new class implements TypeTranslatorInterface {
            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                return null;
            }
        };
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));
        $translator->register(new R4Version(), new R4BVersion(), 'string-primitive', $missing);
        $source = (new R4Patient())->setName((new R4Name())->setFamily(new R4Leaf('family')));

        $result = $translator->translate($source, new R5Version());
        self::assertInstanceOf(R5Patient::class, $result->getResource());
        self::assertNull($result->getResource()->getName());
        self::assertSame(
            ['Patient.name.family', 'Patient.name'],
            array_map(static fn($notice) => $notice->getPath(), $result->getNotices()),
        );
        foreach ($result->getNotices() as $notice) {
            self::assertSame('dropped', $notice->getKind());
        }
    }

    public function testRootNullUsesTheConfiguredUnmappablePolicy(): void
    {
        $missing = new class implements TypeTranslatorInterface {
            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                return null;
            }
        };
        $translator = new Translator();
        $translator->register(new R4Version(), new R5Version(), 'Patient', $missing);

        $this->expectException(MissingXverDefinitionException::class);
        $translator->translate(new R4Patient(), new R5Version());
    }

    public function testContextTranslateReentersTheDispatcherAtTheCurrentPath(): void
    {
        $nested = new class implements TypeTranslatorInterface {
            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                if (!$source instanceof R4Name) {
                    throw new \InvalidArgumentException('Expected an R4 name.');
                }
                $family = $source->getFamily();
                if (null === $family) {
                    return new R4BName();
                }
                $translated = $context->translate($family);
                $target = new R4BName();
                if (null !== $translated) {
                    if (!$translated instanceof R4BLeaf) {
                        throw new \UnexpectedValueException('Expected an R4B leaf.');
                    }
                    $target->setFamily($translated);
                }
                return $target;
            }
        };
        $missing = new class implements TypeTranslatorInterface {
            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                return null;
            }
        };
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::DROP));
        $translator->register(new R4Version(), new R4BVersion(), 'HumanName', $nested);
        $translator->register(new R4Version(), new R4BVersion(), 'string-primitive', $missing);
        $source = (new R4Patient())->setName((new R4Name())->setFamily(new R4Leaf('family')));

        $result = $translator->translate($source, new R5Version());
        self::assertCount(1, $result->getNotices());
        self::assertSame('Patient.name', $result->getNotices()[0]->getPath());
        self::assertNull($result->getResource()->getName()->getFamily());
    }

    public function testNestedTranslateDoesNotInheritTheParentExpectedClass(): void
    {
        $nested = new class implements TypeTranslatorInterface {
            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                if (!$source instanceof R4Name || null === ($family = $source->getFamily())) {
                    return new R4BName();
                }
                $translatedFamily = $context->translate($family);
                if (!$translatedFamily instanceof R4BLeaf) {
                    throw new \UnexpectedValueException('Expected an R4B leaf.');
                }
                return (new R4BName())->setFamily($translatedFamily);
            }
        };
        $translator = new Translator();
        $translator->register(new R4Version(), new R4BVersion(), 'HumanName', $nested);
        $source = (new R4Patient())->setName((new R4Name())->setFamily(new R4Leaf('family')));

        $result = $translator->translate($source, new R4BVersion());

        self::assertInstanceOf(R4BName::class, $result->getResource()->getName());
        self::assertSame('family', $result->getResource()->getName()->getFamily()->getValue());
        self::assertSame([], $result->getNotices());
    }

    public function testRemovedTypeIsNotRescuedByTheDeclaredTargetClass(): void
    {
        $hopMap = new class implements HopMapInterface {
            public function getSourceVersion(): R4Version
            {
                return new R4Version();
            }

            public function getTargetVersion(): R5Version
            {
                return new R5Version();
            }

            public function getRemovedTypes(): array
            {
                return ['HumanName'];
            }

            public function getCodeMaps(): array
            {
                return [];
            }

            public function getXverExtensions(): array
            {
                return [];
            }
        };
        $notices = new TranslationNoticeCollector();
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::DROP),
            'Patient',
            new GenericTypeTranslator(),
            $notices,
            $hopMap,
        );

        self::assertNull($context->translateField('name', new R4Name(), R5Name::class));
        self::assertSame(['Patient.name'], array_map(
            static fn(TranslationNotice $notice): string => $notice->getPath(),
            $notices->getNotices(),
        ));
    }

    public function testThrowPolicyStillReportsNestedPath(): void
    {
        $name = (new R4Name())->setLegacy(new R4Leaf('removed'));
        $source = (new R4Patient())->setName($name);
        $translator = new Translator(new TranslationConfig(OnUnmappableFieldEnum::THROW));

        $this->expectException(UnmappableFieldException::class);
        $this->expectExceptionMessage('Patient.name.legacy');
        $translator->translate($source, new R5Version());
    }

    public function testExtensionPolicyStillReportsNestedPath(): void
    {
        $name = (new R4Name())->setLegacy(new R4Leaf('removed'));
        $source = (new R4Patient())->setName($name);

        $this->expectException(MissingXverDefinitionException::class);
        $this->expectExceptionMessage('Patient.name.legacy');
        (new Translator())->translate($source, new R5Version());
    }

    /**
     * @param array<string, XverExtension> $extensions
     */
    private static function xverHopMap(VersionInterface $target, array $extensions): HopMapInterface
    {
        return new class($target, $extensions) implements HopMapInterface {
            public function __construct(
                private readonly VersionInterface $target,
                private readonly array $extensions,
            ) {
            }

            public function getSourceVersion(): VersionInterface
            {
                return new R4Version();
            }

            public function getTargetVersion(): VersionInterface
            {
                return $this->target;
            }

            public function getRemovedTypes(): array
            {
                return [];
            }

            public function getCodeMaps(): array
            {
                return [];
            }

            public function getXverExtensions(): array
            {
                return $this->extensions;
            }
        };
    }

    /**
     * @param array<string, XverExtension> $extensions
     */
    private static function translatePatientWithXver(
        R4Patient $source,
        array $extensions,
        VersionInterface $target = new R5Version(),
        OnUnmappableFieldEnum $policy = OnUnmappableFieldEnum::EXTENSION,
        null|TranslationNoticeCollector $notices = null,
    ): TypeInterface {
        $context = new TranslationContext(
            new R4Version(),
            $target,
            new TranslationConfig($policy),
            'Patient',
            new TypeTranslatorDispatcher(),
            $notices ?? new TranslationNoticeCollector(),
            self::xverHopMap($target, $extensions),
        );
        $translated = $context->translate($source);
        self::assertNotNull($translated);

        return $translated;
    }

    private static function r4Concept(string $code): R4CodeableConcept
    {
        return (new R4CodeableConcept())->setCoding(
            (new R4Coding())->setSystem(new R4Leaf('http://example.org/cs'))->setCode(new R4Leaf($code)),
        );
    }

    public function testExtensionPolicyEmitsASimpleValueFromTheHopMapDefinition(): void
    {
        $url = 'http://example.org/ext/currency';
        $notices = new TranslationNoticeCollector();
        $translated = self::translatePatientWithXver(
            (new R4Patient())->setCurrency(self::r4Concept('USD')),
            ['Patient.currency' => new XverExtension($url, 'currency', false, ['CodeableConcept'], [], null)],
            notices: $notices,
        );

        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertSame([], $translated->getModifierExtension());
        self::assertCount(1, $translated->getExtension());
        $extension = $translated->getExtension()[0];
        self::assertInstanceOf(R5Extension::class, $extension);
        self::assertSame($url, $extension->getUrl()->getValue());
        self::assertInstanceOf(R5CodeableConcept::class, $extension->getValueCodeableConcept());
        self::assertSame('USD', $extension->getValueCodeableConcept()->getCoding()[0]->getCode()->getValue());
        self::assertSame([], $notices->getNotices(), 'A carried field is not reported as lost.');
    }

    public function testExtensionPolicyEmitsOneExtensionPerRepeatingValueInOrder(): void
    {
        $translated = self::translatePatientWithXver(
            (new R4Patient())->setCategories(self::r4Concept('a'), self::r4Concept('b')),
            ['Patient.categories' => new XverExtension('http://example.org/ext/categories', 'categories', false, ['CodeableConcept'], [], null)],
        );

        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertSame(
            ['a', 'b'],
            array_map(
                static fn(R5Extension $e): string => $e->getValueCodeableConcept()->getCoding()[0]->getCode()->getValue(),
                $translated->getExtension(),
            ),
        );
    }

    public function testModifierDefinitionsAreEmittedAsModifierExtensions(): void
    {
        $translated = self::translatePatientWithXver(
            (new R4Patient())->setCurrency(self::r4Concept('USD')),
            ['Patient.currency' => new XverExtension('http://example.org/ext/currency', 'currency', true, ['CodeableConcept'], [], null)],
        );

        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertSame([], $translated->getExtension());
        self::assertCount(1, $translated->getModifierExtension());
    }

    public function testEnumBoundValuesAreEmittedAsPlainCodes(): void
    {
        $translated = self::translatePatientWithXver(
            (new R4Patient())->setStatus(new R4Status(FixtureStatusEnum::ACTIVE)),
            ['Patient.status' => new XverExtension('http://example.org/ext/status', 'status', false, ['code'], [], null)],
        );

        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertSame('active', $translated->getExtension()[0]->getValueCode()->getValue());
    }

    public function testChoiceDefinitionsCarryAnyOfTheirValueTypes(): void
    {
        $translated = self::translatePatientWithXver(
            (new R4Patient())->setFlag((new R4Coding())->setCode(new R4Leaf('flagged'))),
            ['Patient.flag' => new XverExtension('http://example.org/ext/flag', 'flag', false, ['CodeableConcept', 'Coding'], [], null)],
        );

        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertSame('flagged', $translated->getExtension()[0]->getValueCoding()->getCode()->getValue());
    }

    public function testComplexDefinitionsEmitNestedExtensionsInDefinitionOrder(): void
    {
        $balance = (new R4Balance())
            ->setTerm(self::r4Concept('long'))
            ->setTags(self::r4Concept('t1'), self::r4Concept('t2'))
            ->setDetail((new R4BalanceDetail())->setNote(self::r4Concept('n')));
        $codeable = ['CodeableConcept'];
        $definition = new XverExtension('http://example.org/ext/balance', 'balance', false, [], [
            new XverExtension('detail', 'detail', false, [], [new XverExtension('note', 'note', false, $codeable, [], null)], null),
            new XverExtension('term', 'term', false, $codeable, [], null),
            new XverExtension('tags', 'tags', false, $codeable, [], null),
        ], 'Money');

        $translated = self::translatePatientWithXver((new R4Patient())->setBalance($balance), ['Patient.balance' => $definition]);

        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertCount(1, $translated->getExtension());
        $top = $translated->getExtension()[0];
        self::assertSame('http://example.org/ext/balance', $top->getUrl()->getValue());
        self::assertNull($top->getValueCodeableConcept());
        self::assertSame(
            ['detail', 'term', 'tags', 'tags', 'http://hl7.org/fhir/StructureDefinition/_datatype'],
            array_map(static fn(R5Extension $e): string => $e->getUrl()->getValue(), $top->getExtension()),
        );
        $children = $top->getExtension();
        self::assertSame('note', $children[0]->getExtension()[0]->getUrl()->getValue());
        self::assertSame('n', $children[0]->getExtension()[0]->getValueCodeableConcept()->getCoding()[0]->getCode()->getValue());
        self::assertSame('long', $children[1]->getValueCodeableConcept()->getCoding()[0]->getCode()->getValue());
        self::assertSame('t2', $children[3]->getValueCodeableConcept()->getCoding()[0]->getCode()->getValue());
        self::assertSame('Money', $children[4]->getValueString()->getValue());
    }

    public function testNothingIsEmittedWhenADefinitionCannotCarryEveryValue(): void
    {
        $balance = (new R4Balance())->setTerm(self::r4Concept('long'))->setUncarried(self::r4Concept('lost'));
        $definition = new XverExtension('http://example.org/ext/balance', 'balance', false, [], [
            new XverExtension('term', 'term', false, ['CodeableConcept'], [], null),
        ], null);

        $this->expectException(MissingXverDefinitionException::class);
        $this->expectExceptionMessage('Patient.balance');
        self::translatePatientWithXver((new R4Patient())->setBalance($balance), ['Patient.balance' => $definition]);
    }

    public function testAValueTypeTheDefinitionDoesNotAllowFallsBackToThePolicy(): void
    {
        $this->expectException(MissingXverDefinitionException::class);
        $this->expectExceptionMessage('Patient.flag');
        self::translatePatientWithXver(
            (new R4Patient())->setFlag((new R4Coding())->setCode(new R4Leaf('flagged'))),
            ['Patient.flag' => new XverExtension('http://example.org/ext/flag', 'flag', false, ['CodeableConcept'], [], null)],
        );
    }

    public function testATargetWithoutExtensionFieldsFallsBackToThePolicy(): void
    {
        $this->expectException(MissingXverDefinitionException::class);
        $this->expectExceptionMessage('Patient.currency');
        self::translatePatientWithXver(
            (new R4Patient())->setCurrency(self::r4Concept('USD')),
            ['Patient.currency' => new XverExtension('http://example.org/ext/currency', 'currency', false, ['CodeableConcept'], [], null)],
            new R4BVersion(),
        );
    }

    public function testOnlyTheExtensionPolicyEmitsExtensions(): void
    {
        $extensions = ['Patient.currency' => new XverExtension('http://example.org/ext/currency', 'currency', false, ['CodeableConcept'], [], null)];
        $source = (new R4Patient())->setCurrency(self::r4Concept('USD'));

        $notices = new TranslationNoticeCollector();
        $translated = self::translatePatientWithXver($source, $extensions, new R5Version(), OnUnmappableFieldEnum::DROP, $notices);
        self::assertInstanceOf(R5Patient::class, $translated);
        self::assertSame([], $translated->getExtension());
        self::assertSame(['Patient.currency'], array_map(
            static fn(TranslationNotice $notice): string => $notice->getPath(),
            $notices->getNotices(),
        ));

        $this->expectException(UnmappableFieldException::class);
        self::translatePatientWithXver($source, $extensions, new R5Version(), OnUnmappableFieldEnum::THROW);
    }

    public function testEmissionAppendsToExtensionsThatAreAlreadyPresent(): void
    {
        $url = 'http://example.org/ext/currency';
        $existing = (new R5Extension())->setUrl('http://example.org/ext/existing');
        $target = (new R5Patient())->setExtension($existing);
        $context = new TranslationContext(
            new R4Version(),
            new R5Version(),
            new TranslationConfig(OnUnmappableFieldEnum::EXTENSION),
            'Patient',
            new TypeTranslatorDispatcher(),
            new TranslationNoticeCollector(),
            self::xverHopMap(new R5Version(), [
                'Patient.currency' => new XverExtension($url, 'currency', false, ['CodeableConcept'], [], null),
            ]),
        );
        $metadataFactory = new FieldMetadataFactory();

        $emitted = (new XverExtensionEmitter($metadataFactory))->emit(
            new R4Patient(),
            $target,
            $metadataFactory->forType($target),
            'currency',
            self::r4Concept('USD'),
            $context,
        );

        self::assertTrue($emitted);
        self::assertSame(
            ['http://example.org/ext/existing', $url],
            array_map(static fn(R5Extension $e): string => $e->getUrl()->getValue(), $target->getExtension()),
        );
    }

    public function testNestedTranslatorMustReturnTheTargetVersion(): void
    {
        $wrongVersion = new class implements TypeTranslatorInterface {
            public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
            {
                return new R4Leaf('wrong version');
            }
        };
        $translator = new Translator();
        $translator->register(new R4Version(), new R4BVersion(), 'string-primitive', $wrongVersion);
        $source = (new R4Patient())->setName((new R4Name())->setFamily(new R4Leaf('family')));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('from R4 to R4B returned a type from R4');
        $translator->translate($source, new R5Version());
    }

    public function testEmptyTypeNameCannotBeRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FHIR type name must not be empty.');
        (new Translator())->register(new R4Version(), new R5Version(), '  ', new GenericTypeTranslator());
    }

    public function testGeneratedPostConstructionPropertiesAreReadonly(): void
    {
        foreach ([
                     TranslationContext::class => ['sourceVersion', 'targetVersion', 'config', 'path', 'translator', 'notices', 'hopMap'],
                     TranslationConfig::class => ['onUnmappableField'],
                     TranslationNotice::class => ['path', 'kind', 'sourceVersion', 'targetVersion', 'message'],
                     TranslationResult::class => ['resource', 'notices'],
                     Translator::class => ['config', 'dispatcher'],
                     FieldMetadata::class => ['type', 'collection', 'getter', 'setter'],
                     CodeMapResult::class => ['sourceSystem', 'targetSystem', 'targetCode'],
                     GenericTypeTranslator::class => ['metadataFactory', 'codingCodeMapper', 'xverEmitter', 'xverUnfolder'],
                     TypeTranslatorDispatcher::class => ['defaultTranslator'],
                 ] as $class => $properties) {
            $reflection = new \ReflectionClass($class);
            foreach ($properties as $property) {
                self::assertTrue($reflection->getProperty($property)->isReadOnly(), $class . '::$' . $property);
            }
        }
        $context = new \ReflectionClass(TranslationContext::class);
        foreach ([
            'sourceVersion' => VersionInterface::class,
            'targetVersion' => VersionInterface::class,
            'translator' => TypeTranslatorInterface::class,
        ] as $property => $expectedType) {
            $type = $context->getProperty($property)->getType();
            self::assertInstanceOf(\ReflectionNamedType::class, $type);
            self::assertSame($expectedType, $type->getName());
        }
        self::assertFalse(method_exists(Translator::class, 'registerFactory'));
    }
}
