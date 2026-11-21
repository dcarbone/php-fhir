<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Translation;

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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Config\TranslationPackageConfig;
use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Translation\XverExtensionDefinition;
use DCarbone\PHPFHIR\Translation\XverExtensionReader;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\Types;
use DCarbone\PHPFHIRTests\Profiles\PackageFixtures;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class XverExtensionReaderTest extends TestCase
{
    use PackageFixtures;

    private const DATATYPE_URL = 'http://hl7.org/fhir/StructureDefinition/_datatype';

    private TemporaryDirectory $_tmpdir;
    private string $_root;
    private Version $_version;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'XverExtensionReaderTest');
        $this->_tmpdir->create();
        $this->_root = rtrim($this->_tmpdir->path(''), '/');
        $this->_version = new Version(
            new Config(libraryPath: $this->_root, versions: []),
            new VersionConfig('R5', $this->_root),
        );
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    private function type(Types $types, string $name, array $properties): Type
    {
        $type = $types->addOrReturnType(new Type($this->_version, $name));
        foreach ($properties as $propertyName => $valueType) {
            $type->getProperties()->addOrReturnProperty(new Property(
                $type,
                new \SimpleXMLElement('<element/>'),
                '',
                name: $propertyName,
                valueFHIRType: $valueType instanceof Type ? $valueType : null,
                valueFHIRTypeName: $valueType instanceof Type ? '' : $valueType,
            ));
        }
        return $type;
    }

    private function sourceTypes(): Types
    {
        $types = new Types($this->_version);
        $balance = $this->type($types, 'Account.Balance', [
            'aggregate' => 'CodeableConcept',
            'term' => 'CodeableConcept',
            'estimate' => 'boolean',
            'amount' => 'Money',
        ]);
        $reference = $this->type($types, 'CodeableReference', [
            'concept' => 'CodeableConcept',
            'reference' => 'Reference',
        ]);
        $diagnosis = $this->type($types, 'Account.Diagnosis', [
            'sequence' => 'positiveInt',
            'condition' => $reference,
        ]);
        $this->type($types, 'Account', [
            'currency' => 'CodeableConcept',
            'status' => 'code',
            'balance' => $balance,
            'diagnosis' => $diagnosis,
        ]);
        $this->type($types, 'Observation', [
            'valueQuantity' => 'Quantity',
            'valueString' => 'string',
            'status' => 'code',
        ]);
        return $types;
    }

    /**
     * @param array<string, mixed> $element
     */
    private static function slice(string $id, string $name, array $element = []): array
    {
        return array_merge(['id' => $id . ':' . $name, 'path' => 'Extension.extension', 'sliceName' => $name, 'min' => 0, 'max' => '1'], $element);
    }

    /**
     * @param list<string> $types Value types of the child.
     */
    private static function valueSlice(string $parentId, string $name, array $types, array $slice = []): array
    {
        $id = $parentId . ':' . $name;
        return [
            self::slice($parentId, $name, $slice),
            ['id' => $id . '.url', 'path' => 'Extension.extension.url', 'min' => 1, 'max' => '1', 'fixedUri' => $name],
            [
                'id' => $id . '.value[x]',
                'path' => 'Extension.extension.value[x]',
                'min' => 0,
                'max' => '1',
                'type' => array_map(static fn(string $code): array => ['code' => $code], $types),
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @return array<string, mixed>
     */
    private static function extension(string $element, array $elements, string $urlPrefix = 'http://hl7.org/fhir/5.0/StructureDefinition/extension-'): array
    {
        $url = $urlPrefix . $element;
        return self::structureDefinition('ext-' . $element, 'Extension', 'constraint', [
            'url' => $url,
            'kind' => 'complex-type',
            'context' => [['type' => 'element', 'expression' => explode('.', $element)[0]]],
            'differential' => ['element' => array_merge(
                [['id' => 'Extension', 'path' => 'Extension', 'min' => 0, 'max' => '*']],
                $elements,
                [['id' => 'Extension.url', 'path' => 'Extension.url', 'min' => 1, 'max' => '1', 'fixedUri' => $url]],
            )],
        ]);
    }

    /**
     * @param list<string> $types
     */
    private static function simple(string $element, array $types): array
    {
        return self::extension($element, [[
            'id' => 'Extension.value[x]',
            'path' => 'Extension.value[x]',
            'min' => 0,
            'max' => '1',
            'type' => array_map(static fn(string $code): array => ['code' => $code], $types),
        ]]);
    }

    /**
     * @param array<string, array<string, mixed>> $extensions
     * @return array<string, XverExtensionDefinition>
     */
    private function read(array $extensions): array
    {
        $files = ['package.json' => self::manifest('example.xver')];
        foreach ($extensions as $name => $sd) {
            $files['StructureDefinition-' . $name . '.json'] = $sd;
        }
        $source = self::writePackageDir($this->_root . '/pkg-' . md5(json_encode($files)), $files);

        return (new XverExtensionReader())->read(
            TranslationPackageConfig::fromArray(['sourceVersion' => 'R5', 'targetVersion' => 'R4', 'source' => $source]),
            $this->sourceTypes(),
        );
    }

    public function testSimpleExtensionResolvesToItsSourceField(): void
    {
        $definitions = $this->read(['currency' => self::simple('Account.currency', ['CodeableConcept'])]);

        $this->assertSame(['Account.currency'], array_keys($definitions));
        $definition = $definitions['Account.currency'];
        $this->assertSame('http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.currency', $definition->url);
        $this->assertSame('currency', $definition->field);
        $this->assertFalse($definition->modifier);
        $this->assertSame(['CodeableConcept'], $definition->valueTypes);
        $this->assertSame([], $definition->children);
        $this->assertNull($definition->datatype);
    }

    public function testComplexExtensionResolvesEachSliceAndSkipsProhibitedOnes(): void
    {
        $id = 'Extension.extension';
        $definitions = $this->read(['balance' => self::extension('Account.balance', array_merge(
            [['id' => $id, 'path' => $id, 'min' => 1, 'max' => '*']],
            self::valueSlice($id, 'aggregate', ['CodeableConcept']),
            self::valueSlice($id, 'term', ['CodeableConcept'], ['max' => '0']),
            self::valueSlice($id, 'estimate', ['boolean']),
            self::valueSlice($id, 'amount', ['Money'], ['min' => 1]),
            [['id' => 'Extension.value[x]', 'path' => 'Extension.value[x]', 'min' => 0, 'max' => '0']],
        ))]);

        $this->assertSame(['Account.balance'], array_keys($definitions));
        $this->assertSame([], $definitions['Account.balance']->valueTypes);
        $this->assertSame(
            [['aggregate', 'aggregate'], ['estimate', 'estimate'], ['amount', 'amount']],
            array_map(static fn(XverExtensionDefinition $child): array => [$child->url, $child->field], $definitions['Account.balance']->children),
        );
        $this->assertSame(
            [['CodeableConcept'], ['boolean'], ['Money']],
            array_map(static fn(XverExtensionDefinition $child): array => $child->valueTypes, $definitions['Account.balance']->children),
        );
    }

    public function testNestedComplexExtensionCapturesItsDatatype(): void
    {
        $id = 'Extension.extension';
        $condition = $id . ':condition';
        $definitions = $this->read(['diagnosis' => self::extension('Account.diagnosis', array_merge(
            [['id' => $id, 'path' => $id, 'min' => 1, 'max' => '*']],
            self::valueSlice($id, 'sequence', ['positiveInt']),
            [
                self::slice($id, 'condition', ['min' => 1]),
                ['id' => $condition . '.extension', 'path' => 'Extension.extension.extension', 'min' => 1, 'max' => '*'],
                self::slice($condition . '.extension', '_datatype', ['min' => 1]),
                ['id' => $condition . '.extension:_datatype.url', 'path' => 'Extension.extension.extension.url', 'min' => 1, 'max' => '1', 'fixedUri' => self::DATATYPE_URL],
                ['id' => $condition . '.extension:_datatype.value[x]', 'path' => 'Extension.extension.extension.value[x]', 'min' => 1, 'max' => '1', 'type' => [['code' => 'string']], 'fixedString' => 'CodeableReference'],
            ],
            self::valueSlice($condition . '.extension', 'concept', ['CodeableConcept']),
            self::valueSlice($condition . '.extension', 'reference', ['Reference']),
        ))]);

        $this->assertSame(['Account.diagnosis'], array_keys($definitions));
        [$sequence, $conditionDefinition] = $definitions['Account.diagnosis']->children;
        $this->assertSame('sequence', $sequence->field);
        $this->assertSame([], $sequence->children);
        $this->assertSame('condition', $conditionDefinition->url);
        $this->assertSame('condition', $conditionDefinition->field);
        $this->assertSame('CodeableReference', $conditionDefinition->datatype);
        $this->assertSame(
            [['concept', 'concept'], ['reference', 'reference']],
            array_map(static fn(XverExtensionDefinition $child): array => [$child->url, $child->field], $conditionDefinition->children),
        );
    }

    public function testRootModifierFlagIsCaptured(): void
    {
        $sd = self::simple('Account.status', ['code']);
        $sd['differential']['element'][0]['isModifier'] = true;
        $definitions = $this->read(['status' => $sd]);

        $this->assertTrue($definitions['Account.status']->modifier);
    }

    public function testChoiceElementResolvesToEveryConcreteSourceField(): void
    {
        $definitions = $this->read(['value' => self::simple('Observation.value', ['Quantity', 'string'])]);

        $this->assertSame(['Observation.valueQuantity', 'Observation.valueString'], array_keys($definitions));
        $this->assertSame('valueQuantity', $definitions['Observation.valueQuantity']->field);
        $this->assertSame('valueString', $definitions['Observation.valueString']->field);
        $this->assertSame($definitions['Observation.valueString']->url, $definitions['Observation.valueQuantity']->url);
    }

    public function testChoiceElementIsLimitedToTheTypesTheExtensionCarries(): void
    {
        $definitions = $this->read(['value' => self::simple('Observation.value', ['Quantity'])]);

        $this->assertSame(['Observation.valueQuantity'], array_keys($definitions));
        $this->assertSame(['Quantity'], $definitions['Observation.valueQuantity']->valueTypes);
    }

    public function testRepeatedValueTypeCodesAreCollapsed(): void
    {
        $definitions = $this->read(['currency' => self::simple('Account.currency', ['CodeableConcept', 'CodeableConcept'])]);

        $this->assertSame(['CodeableConcept'], $definitions['Account.currency']->valueTypes);
    }

    public function testNonChoiceFieldKeepsItsOwnTypeWhenTheExtensionValueTypeDiffers(): void
    {
        $definitions = $this->read(['status' => self::simple('Account.status', ['code'])]);

        $this->assertSame(['code'], $definitions['Account.status']->valueTypes);
    }

    public function testExtensionsThatCannotBeResolvedAreOmitted(): void
    {
        $id = 'Extension.extension';
        $definitions = $this->read([
            'resolved' => self::simple('Account.currency', ['CodeableConcept']),
            'unknown-type' => self::simple('Removed.field', ['string']),
            'unknown-field' => self::simple('Account.nonexistent', ['string']),
            'unknown-slice' => self::extension('Account.balance', array_merge(
                [['id' => $id, 'path' => $id, 'min' => 1, 'max' => '*']],
                self::valueSlice($id, 'aggregate', ['CodeableConcept']),
                self::valueSlice($id, 'nonexistent', ['string']),
            )),
        ]);

        $this->assertSame(['Account.currency'], array_keys($definitions));
    }

    public function testOnlyCrossVersionExtensionsAreRead(): void
    {
        $profile = self::structureDefinition('account-profile', 'Account');
        $other = self::simple('Account.currency', ['CodeableConcept']);
        $other['url'] = 'http://example.org/fhir/StructureDefinition/account-currency';
        $nonExtension = self::structureDefinition('ext-account-status', 'Account', 'constraint', [
            'url' => 'http://hl7.org/fhir/5.0/StructureDefinition/extension-Account.status',
        ]);

        $this->assertSame([], $this->read(['profile' => $profile, 'other' => $other, 'non-extension' => $nonExtension]));
    }

    public function testTwoExtensionsClaimingOneSourceFieldAreRejected(): void
    {
        $current = self::simple('Account.currency', ['CodeableConcept']);
        $legacy = self::extension('Account.currency', [[
            'id' => 'Extension.value[x]',
            'path' => 'Extension.value[x]',
            'type' => [['code' => 'CodeableConcept']],
        ]], 'http://hl7.org/fhir/4.0/StructureDefinition/extension-');
        $legacy['id'] = 'ext-legacy-Account.currency';

        $this->expectException(ProfileDefinitionException::class);
        $this->expectExceptionMessage('both carry source field "Account.currency"');
        $this->read(['one' => $current, 'two' => $legacy]);
    }

    public function testDefinitionRequiresAUrlAndField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new XverExtensionDefinition('', 'currency');
    }
}
