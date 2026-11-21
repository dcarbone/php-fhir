<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests;

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

use DCarbone\PHPFHIR\Builder;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Config\TranslationPackageConfig;
use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Translation\CodeMapDefinition;
use DCarbone\PHPFHIR\Translation\HopMapBuilder;
use DCarbone\PHPFHIR\Translation\HopMapDefinition;
use DCarbone\PHPFHIR\Translation\TranslationPackageReader;
use DCarbone\PHPFHIR\Translation\XverExtensionDefinition;
use DCarbone\PHPFHIR\Utilities\FileUtils;
use DCarbone\PHPFHIR\Version\Definition;
use DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class BuilderTranslationTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BuilderTranslationTest');
        $this->_tmpdir->create();
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function config(): Config
    {
        $directory = fn(string $name): string => $this->_tmpdir->path($name);
        foreach (['out', 'r4', 'r5'] as $name) {
            $path = $directory($name);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        return Config::fromArray([
            'libraryPath' => $directory('out'),
            'versions' => [
                ['name' => 'R4', 'schemaPath' => $directory('r4')],
                ['name' => 'R5', 'schemaPath' => $directory('r5')],
            ],
        ]);
    }

    public function testCoreTranslationFilesRenderAndRegistryUsesSelectedVersions(): void
    {
        $config = $this->config();
        $builder = new Builder($config);

        foreach (glob(PHPFHIR_TEMPLATE_CORE_DIR . '/translation/*.php') as $templatePath) {
            $template = file_get_contents($templatePath);
            self::assertIsString($template);
            self::assertStringContainsString(
                'Licensed under the Apache License, Version 2.0',
                $template,
                $templatePath,
            );
        }

        $builder->writeLibraryCoreEntities(['R4']);

        foreach ($config->getCoreFiles()->getIterator() as $coreFile) {
            $generated = file_get_contents($coreFile->getFilepath());
            self::assertIsString($generated);
            token_get_all($generated, TOKEN_PARSE);
        }

        $genericTranslator = $config->getCoreFiles()
            ->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_GENERIC_TYPE_TRANSLATOR);
        $genericTranslatorContents = file_get_contents($genericTranslator->getFilepath());
        self::assertIsString($genericTranslatorContents);
        self::assertStringContainsString(
            'if ($source instanceof LeafTypeInterface || $target instanceof LeafTypeInterface)',
            $genericTranslatorContents,
        );

        $translator = $config->getCoreFiles()
            ->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_TRANSLATOR);
        $translatorContents = file_get_contents($translator->getFilepath());
        self::assertIsString($translatorContents);
        self::assertStringNotContainsString('registerFactory', $translatorContents);
        self::assertStringContainsString('$context->translate($resource)', $translatorContents);

        $registryFile = $config->getCoreFiles()
            ->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_VERSION_REGISTRY);
        $contents = file_get_contents($registryFile->getFilepath());

        self::assertIsString($contents);
        $r4Namespace = $config->getVersion('R4')->getFullyQualifiedName(false);
        $r5Namespace = $config->getVersion('R5')->getFullyQualifiedName(false);
        self::assertStringContainsString(var_export($r4Namespace, true), $contents);
        self::assertStringNotContainsString(var_export($r5Namespace, true), $contents);
    }

    public function testGeneratedAutoloaderLoadsHopMapClasses(): void
    {
        $libraryPath = $this->_tmpdir->path('autoload-library');
        if (!is_dir($libraryPath)) {
            mkdir($libraryPath);
        }
        $config = Config::fromArray([
            'libraryPath' => $libraryPath,
            'libraryNamespacePrefix' => 'BuilderTranslationAutoloadGenerated',
            'versions' => [
                ['name' => 'R4', 'schemaPath' => $libraryPath],
                ['name' => 'R5', 'schemaPath' => $libraryPath],
            ],
        ]);
        $autoloader = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_CLASSNAME_AUTOLOADER);
        $mapClass = $config->getFullyQualifiedName(false, 'Translation', 'Maps', 'HopMapAutoloadProbe');
        $mapPath = FileUtils::buildClassFilepath($libraryPath, $mapClass);
        FileUtils::mkdirRecurse($mapPath);
        FileUtils::mkdirRecurse($autoloader->getFilepath());
        self::assertNotFalse(file_put_contents(
            $mapPath,
            "<?php declare(strict_types=1);\nnamespace BuilderTranslationAutoloadGenerated\\Translation\\Maps;\nfinal class HopMapAutoloadProbe {}\n",
        ));

        $generated = Templates::renderCoreFile($config, $autoloader, [
            'config' => $config,
            'versionNames' => ['R4', 'R5'],
            'hopMapClasses' => ["R4\0R5" => $mapClass],
        ]);
        self::assertNotFalse(file_put_contents($autoloader->getFilepath(), $generated));
        token_get_all($generated, TOKEN_PARSE);

        require $autoloader->getFilepath();
        $autoloaderClass = $autoloader->getFullyQualifiedName(false);
        try {
            self::assertTrue(class_exists($mapClass));
            self::assertSame($mapPath, (new \ReflectionClass($mapClass))->getFileName());
        } finally {
            $autoloaderClass::unregister();
        }
    }

    public function testHopMapBuilderRecordsRemovedTypes(): void
    {
        $config = $this->config();
        $r4 = $config->getVersion('R4');
        $r5 = $config->getVersion('R5');
        $sourceTypes = new Types($r4);
        $targetTypes = new Types($r5);

        $sourceTypes->addOrReturnType(new Type($r4, 'Patient'));
        $sourceTypes->addOrReturnType(new Type($r4, 'OldResource'));
        $targetTypes->addOrReturnType(new Type($r5, 'Patient'));

        $builder = new HopMapBuilder();
        $definition = $builder->build($sourceTypes, $targetTypes);

        self::assertInstanceOf(HopMapDefinition::class, $definition);
        self::assertSame(['OldResource'], $definition->getRemovedTypes());

        $generated = $builder->renderMapDefinition($r4, $r5, $definition);
        self::assertNotEmpty(token_get_all($generated, TOKEN_PARSE));
        self::assertStringContainsString('final class HopMap_R4ToR5', $generated);
        self::assertStringContainsString("'OldResource',", $generated);
        self::assertStringNotContainsString('getFieldRules', $generated);
        $reverse = $builder->build($targetTypes, $sourceTypes);
        self::assertSame([], $reverse->getRemovedTypes());
        $reverseGenerated = $builder->renderMapDefinition($r5, $r4, $reverse);
        self::assertNotEmpty(token_get_all($reverseGenerated, TOKEN_PARSE));
        self::assertStringContainsString('final class HopMap_R5ToR4', $reverseGenerated);
    }

    public function testHopMapBuilderDoesNotTreatRenamedValueSetEnumerationsAsRemoved(): void
    {
        $config = $this->config();
        $r4 = $config->getVersion('R4');
        $r5 = $config->getVersion('R5');
        $sourceTypes = new Types($r4);
        $targetTypes = new Types($r5);

        $list = new Type($r4, 'AdministrativeGender-list');
        $list->addEnumerationValue(new EnumerationValue('male'));
        $sourceTypes->addOrReturnType($list);
        $sourceTypes->addOrReturnType(new Type($r4, 'OldResource'));

        $builder = new HopMapBuilder();

        self::assertSame(['OldResource'], $builder->build($sourceTypes, $targetTypes)->getRemovedTypes());
    }

    public function testHopMapDefinitionIsAnImmutableTypedDescriptor(): void
    {
        $definition = new HopMapDefinition(
            removedTypes: ['OldResource'],
        );

        self::assertSame(['OldResource'], $definition->getRemovedTypes());
        foreach (['removedTypes', 'codeMaps', 'xverExtensions'] as $name) {
            self::assertTrue((new \ReflectionProperty(HopMapDefinition::class, $name))->isReadOnly());
        }
    }

    public function testHopMapRendererIncludesTypedCodeMapDefinitions(): void
    {
        $config = $this->config();
        $builder = new HopMapBuilder();
        $definition = new HopMapDefinition(
            removedTypes: [],
            codeMaps: [
                new CodeMapDefinition('http://example.org/source', 'http://example.org/target', [
                    'legacy' => 'current',
                    'unmapped' => null,
                ]),
            ],
        );

        $generated = $builder->renderMapDefinition($config->getVersion('R4'), $config->getVersion('R5'), $definition);
        self::assertNotEmpty(token_get_all($generated, TOKEN_PARSE));
        self::assertStringContainsString("'http://example.org/source'", $generated);
        self::assertStringContainsString("'legacy' => 'current'", $generated);
        self::assertStringContainsString("'unmapped' => NULL", $generated);
        self::assertStringContainsString('public function getCodeMaps(): array', $generated);
    }

    public function testHopMapRendererIncludesTypedXverExtensionDefinitions(): void
    {
        $config = $this->config();
        $builder = new HopMapBuilder();
        $definition = new HopMapDefinition(
            removedTypes: [],
            xverExtensions: [
                'Account.currency' => new XverExtensionDefinition(
                    url: 'http://hl7.org/fhir/4.0/StructureDefinition/extension-Account.currency',
                    field: 'currency',
                    valueTypes: ['CodeableConcept'],
                ),
                'Account.diagnosis' => new XverExtensionDefinition(
                    url: 'http://hl7.org/fhir/4.0/StructureDefinition/extension-Account.diagnosis',
                    field: 'diagnosis',
                    modifier: true,
                    children: [
                        new XverExtensionDefinition(url: 'condition', field: 'condition', datatype: 'CodeableReference'),
                    ],
                ),
            ],
        );

        $generated = $builder->renderMapDefinition($config->getVersion('R4'), $config->getVersion('R5'), $definition);
        self::assertNotEmpty(token_get_all($generated, TOKEN_PARSE));
        self::assertStringContainsString('public function getXverExtensions(): array', $generated);
        self::assertStringContainsString("'Account.currency' => new XverExtension(", $generated);
        self::assertStringContainsString("'Account.diagnosis' => new XverExtension(", $generated);
        self::assertStringContainsString("'CodeableReference'", $generated);

        $empty = $builder->renderMapDefinition(
            $config->getVersion('R4'),
            $config->getVersion('R5'),
            new HopMapDefinition(removedTypes: []),
        );
        self::assertNotEmpty(token_get_all($empty, TOKEN_PARSE));
        self::assertStringContainsString('public function getXverExtensions(): array', $empty);
        self::assertStringNotContainsString('new XverExtension(', $empty);
    }

    public function testConfiguredTranslationPackageIsWiredIntoItsDirectionalHopMap(): void
    {
        $libraryPath = $this->_tmpdir->path('package-library');
        $packagePath = $this->_tmpdir->path('xver-package/package');
        $r4SchemaPath = $this->_tmpdir->path('package-r4');
        $r4bSchemaPath = $this->_tmpdir->path('package-r4b');
        $r5SchemaPath = $this->_tmpdir->path('package-r5');
        foreach ([$libraryPath, $packagePath, $r4SchemaPath, $r4bSchemaPath, $r5SchemaPath] as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
        file_put_contents($r4SchemaPath . '/fhir-base.xsd', "<!--\nGenerated on 2022-01-01 for FHIR v4.0.1\n-->\n");
        file_put_contents($r4bSchemaPath . '/fhir-base.xsd', "<!--\nGenerated on 2022-01-01 for FHIR v4.3.0\n-->\n");
        file_put_contents($r5SchemaPath . '/fhir-base.xsd', "<!--\nGenerated on 2023-01-01 for FHIR v5.0.0\n-->\n");
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

        file_put_contents($packagePath . '/StructureDefinition-extension-Account.currency.json', json_encode([
            'resourceType' => 'StructureDefinition',
            'id' => 'extension-Account.currency',
            'url' => 'http://hl7.org/fhir/4.0/StructureDefinition/extension-Account.currency',
            'name' => 'AccountCurrency',
            'kind' => 'complex-type',
            'abstract' => false,
            'type' => 'Extension',
            'baseDefinition' => 'http://hl7.org/fhir/StructureDefinition/Extension',
            'derivation' => 'constraint',
            'context' => [['type' => 'element', 'expression' => 'Account']],
            'differential' => ['element' => [
                ['id' => 'Extension', 'path' => 'Extension', 'min' => 0, 'max' => '*'],
                [
                    'id' => 'Extension.url',
                    'path' => 'Extension.url',
                    'min' => 1,
                    'max' => '1',
                    'fixedUri' => 'http://hl7.org/fhir/4.0/StructureDefinition/extension-Account.currency',
                ],
                [
                    'id' => 'Extension.value[x]',
                    'path' => 'Extension.value[x]',
                    'min' => 0,
                    'max' => '1',
                    'type' => [['code' => 'CodeableConcept']],
                ],
            ]],
        ], JSON_THROW_ON_ERROR));

        $config = Config::fromArray([
            'libraryPath' => $libraryPath,
            'libraryNamespacePrefix' => 'BuilderTranslationPackageGenerated',
            'versions' => [
                ['name' => 'R4', 'schemaPath' => $r4SchemaPath],
                ['name' => 'R4B', 'schemaPath' => $r4bSchemaPath],
                ['name' => 'R5', 'schemaPath' => $r5SchemaPath],
            ],
            'translationPackages' => [[
                'sourceVersion' => 'R4',
                'targetVersion' => 'R5',
                'source' => $this->_tmpdir->path('xver-package'),
            ]],
        ]);
        foreach (['R4', 'R4B', 'R5'] as $versionName) {
            $version = $config->getVersion($versionName);
            $types = new Types($version);
            if ('R4' === $versionName) {
                $account = $types->addOrReturnType(new Type($version, 'Account'));
                $account->getProperties()->addOrReturnProperty(new Property(
                    $account,
                    new \SimpleXMLElement('<element/>'),
                    '',
                    name: 'currency',
                    valueFHIRTypeName: 'CodeableConcept',
                ));
            }
            (new \ReflectionProperty(Definition::class, '_types'))
                ->setValue($version->getDefinition(), $types);
        }

        (new Builder($config))->writeLibraryTranslationMaps();

        $forwardClass = $config->getFullyQualifiedName(false, 'Translation', 'Maps', 'HopMap_R4ToR5');
        $forwardPath = FileUtils::buildClassFilepath($libraryPath, $forwardClass);
        $forward = file_get_contents($forwardPath);
        self::assertIsString($forward);
        self::assertStringContainsString("'legacy' => 'current'", $forward);
        self::assertStringContainsString("'Account.currency' => new XverExtension(", $forward);
        self::assertStringContainsString(
            "'http://hl7.org/fhir/4.0/StructureDefinition/extension-Account.currency'",
            $forward,
        );
        $reversePath = FileUtils::buildClassFilepath(
            $libraryPath,
            $config->getFullyQualifiedName(false, 'Translation', 'Maps', 'HopMap_R5ToR4'),
        );
        self::assertFileExists($reversePath);
        $reverse = file_get_contents($reversePath);
        self::assertIsString($reverse);
        self::assertStringContainsString('final class HopMap_R5ToR4', $reverse);
        self::assertStringNotContainsString("'legacy' => 'current'", $reverse);
    }

    public function testTranslationPackageReaderKeepsNumericCodesAsStrings(): void
    {
        $packagePath = $this->_tmpdir->path('numeric-package/package');
        file_put_contents($packagePath . '/package.json', json_encode([
            'name' => 'example.numeric',
            'version' => '1.0.0',
        ], JSON_THROW_ON_ERROR));
        file_put_contents($packagePath . '/ConceptMap-numeric.json', json_encode([
            'resourceType' => 'ConceptMap',
            'id' => 'numeric',
            'group' => [[
                'source' => 'http://example.org/source',
                'target' => 'http://example.org/target',
                'element' => [
                    ['code' => '100', 'target' => [['code' => '123', 'relationship' => 'equivalent']]],
                    ['code' => 'b', 'target' => [['code' => '7', 'equivalence' => 'equivalent']]],
                    ['code' => 'a', 'target' => [['code' => 'z', 'relationship' => 'equivalent']]],
                ],
            ]],
        ], JSON_THROW_ON_ERROR));

        $codeMaps = (new TranslationPackageReader())->readCodeMaps(
            new TranslationPackageConfig('R4', 'R5', $this->_tmpdir->path('numeric-package')),
        );

        self::assertCount(1, $codeMaps);
        $mappings = $codeMaps[0]->getCodeMappings();
        self::assertSame(['100', 'a', 'b'], array_map('strval', array_keys($mappings)));
        self::assertSame('123', $mappings['100']);
        self::assertSame('7', $mappings['b']);
        self::assertSame('z', $mappings['a']);

        $generated = (new HopMapBuilder())->renderMapDefinition(
            $this->config()->getVersion('R4'),
            $this->config()->getVersion('R5'),
            new HopMapDefinition([], $codeMaps),
        );
        self::assertStringContainsString("100 => '123'", $generated);
        self::assertStringContainsString("'b' => '7'", $generated);
    }

    public function testVersionRegistryMatchesNestedVersionNamespacesMostSpecificFirst(): void
    {
        $config = Config::fromArray([
            'libraryPath' => $this->_tmpdir->path('nested-out'),
            'versions' => [
                ['name' => 'R4', 'schemaPath' => $this->_tmpdir->path('nested-r4')],
                ['name' => 'R4Custom', 'namespace' => 'R4\\Custom', 'schemaPath' => $this->_tmpdir->path('nested-custom')],
            ],
        ]);
        (new Builder($config))->writeLibraryCoreEntities(['R4', 'R4Custom']);

        $registryFile = $config->getCoreFiles()
            ->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_VERSION_REGISTRY);
        $contents = file_get_contents($registryFile->getFilepath());
        self::assertIsString($contents);
        $customPosition = strpos($contents, var_export($config->getVersion('R4Custom')->getFullyQualifiedName(false), true));
        $r4Position = strpos($contents, var_export($config->getVersion('R4')->getFullyQualifiedName(false), true) . ' =>');
        self::assertIsInt($customPosition);
        self::assertIsInt($r4Position);
        self::assertLessThan($r4Position, $customPosition);
        self::assertStringNotContainsString('->value', $contents);
    }

    public function testHopMapRendererRejectsArrayDefinitions(): void
    {
        $config = $this->config();

        $this->expectException(\TypeError::class);
        (new HopMapBuilder())->renderMapDefinition($config->getVersion('R4'), $config->getVersion('R5'), []);
    }
}
