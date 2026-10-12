<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Profiles;

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
use DCarbone\PHPFHIR\Profiles\ProfileBinding;
use DCarbone\PHPFHIR\Profiles\ProfileDefinitionException;
use DCarbone\PHPFHIR\Profiles\ProfileElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProfileElementTest extends TestCase
{
    public function testMinimalElement(): void
    {
        $e = ProfileElement::fromArray(['id' => 'Patient.name', 'path' => 'Patient.name']);
        $this->assertSame('Patient.name', $e->getId());
        $this->assertSame('Patient.name', $e->getPath());
        $this->assertSame('name', $e->getName());
        $this->assertSame('Patient', $e->getParentId());
        $this->assertFalse($e->isRoot());
        $this->assertFalse($e->isChoice());
        $this->assertFalse($e->isSlice());
        $this->assertFalse($e->isWithinSlice());
        $this->assertNull($e->getMin());
        $this->assertNull($e->getMax());
        $this->assertFalse($e->isRequired());
        $this->assertFalse($e->isProhibited());
        $this->assertSame([], $e->getTypes());
        $this->assertNull($e->getFixedValue());
        $this->assertNull($e->getBinding());
        $this->assertNull($e->getSlicing());
        $this->assertSame([], $e->getConstraints());
        $this->assertFalse($e->isMustSupport());
        $this->assertNull($e->getContentReference());
    }

    public function testRootElement(): void
    {
        $e = ProfileElement::fromArray(['id' => 'Patient', 'path' => 'Patient']);
        $this->assertTrue($e->isRoot());
        $this->assertNull($e->getParentId());
        $this->assertSame('Patient', $e->getName());
    }

    public function testCardinality(): void
    {
        $required = ProfileElement::fromArray(['id' => 'A.b', 'path' => 'A.b', 'min' => 1, 'max' => '1']);
        $this->assertSame(1, $required->getMin());
        $this->assertSame(1, $required->getMax());
        $this->assertTrue($required->isRequired());

        $many = ProfileElement::fromArray(['id' => 'A.b', 'path' => 'A.b', 'min' => 0, 'max' => '*']);
        $this->assertSame(PHPFHIR_UNLIMITED, $many->getMax());
        $this->assertFalse($many->isRequired());

        $prohibited = ProfileElement::fromArray(['id' => 'A.b', 'path' => 'A.b', 'min' => 0, 'max' => '0']);
        $this->assertTrue($prohibited->isProhibited());
    }

    public static function invalidElementProvider(): array
    {
        return [
            'no id' => [['path' => 'A.b']],
            'no path' => [['id' => 'A.b']],
            'empty id' => [['id' => '', 'path' => 'A.b']],
            'negative min' => [['id' => 'A.b', 'path' => 'A.b', 'min' => -1]],
            'string min' => [['id' => 'A.b', 'path' => 'A.b', 'min' => '1']],
            'bad max' => [['id' => 'A.b', 'path' => 'A.b', 'max' => 'many']],
            'int max' => [['id' => 'A.b', 'path' => 'A.b', 'max' => 1]],
            'type without code' => [['id' => 'A.b', 'path' => 'A.b', 'type' => [['profile' => ['x']]]]],
            'binding without strength' => [['id' => 'A.b', 'path' => 'A.b', 'binding' => ['valueSet' => 'x']]],
            'slicing without rules' => [['id' => 'A.b', 'path' => 'A.b', 'slicing' => ['ordered' => true]]],
            'bad discriminator' => [['id' => 'A.b', 'path' => 'A.b', 'slicing' => ['rules' => 'open', 'discriminator' => [['type' => 'value']]]]],
            'constraint without key' => [['id' => 'A.b', 'path' => 'A.b', 'constraint' => [['severity' => 'error', 'human' => 'x']]]],
        ];
    }

    #[DataProvider('invalidElementProvider')]
    public function testInvalidElementThrows(array $data): void
    {
        $this->expectException(ProfileDefinitionException::class);
        ProfileElement::fromArray($data);
    }

    public function testChoiceElementAndTypes(): void
    {
        $e = ProfileElement::fromArray([
            'id' => 'Patient.deceased[x]',
            'path' => 'Patient.deceased[x]',
            'type' => [['code' => 'boolean'], ['code' => 'dateTime']],
        ]);
        $this->assertTrue($e->isChoice());
        $this->assertSame('deceased[x]', $e->getName());
        $this->assertSame(['boolean', 'dateTime'], $e->getTypeCodes());
    }

    public function testTypeProfilesAndTargetProfiles(): void
    {
        $e = ProfileElement::fromArray([
            'id' => 'A.ref',
            'path' => 'A.ref',
            'type' => [['code' => 'Reference', 'targetProfile' => ['http://x/P1', 'http://x/P2']], ['code' => 'Extension', 'profile' => ['http://x/E']]],
        ]);
        [$ref, $ext] = $e->getTypes();
        $this->assertSame(['http://x/P1', 'http://x/P2'], $ref->getTargetProfiles());
        $this->assertSame([], $ref->getProfiles());
        $this->assertSame(['http://x/E'], $ext->getProfiles());
    }

    public static function fixedProvider(): array
    {
        return [
            'fixedUri' => ['fixedUri', 'http://x', 'fixed', 'Uri'],
            'fixedCode' => ['fixedCode', 'final', 'fixed', 'Code'],
            'patternCodeableConcept' => ['patternCodeableConcept', ['coding' => [['code' => 'a']]], 'pattern', 'CodeableConcept'],
            'patternCoding' => ['patternCoding', ['code' => 'a'], 'pattern', 'Coding'],
        ];
    }

    #[DataProvider('fixedProvider')]
    public function testFixedAndPatternValues(string $key, mixed $value, string $kind, string $type): void
    {
        $e = ProfileElement::fromArray(['id' => 'A.b', 'path' => 'A.b', $key => $value]);
        $fixed = $e->getFixedValue();
        $this->assertNotNull($fixed);
        $this->assertSame($kind, $fixed->getKind());
        $this->assertSame('fixed' === $kind, $fixed->isFixed());
        $this->assertSame('pattern' === $kind, $fixed->isPattern());
        $this->assertSame($type, $fixed->getType());
        $this->assertSame($value, $fixed->getValue());
    }

    public function testFixedValueWithFalseyValue(): void
    {
        $e = ProfileElement::fromArray(['id' => 'A.b', 'path' => 'A.b', 'fixedBoolean' => false]);
        $this->assertFalse($e->getFixedValue()->getValue());
    }

    public function testBinding(): void
    {
        $e = ProfileElement::fromArray([
            'id' => 'A.b',
            'path' => 'A.b',
            'binding' => ['strength' => 'required', 'valueSet' => 'http://x/vs|1', 'description' => 'desc'],
        ]);
        $b = $e->getBinding();
        $this->assertSame(ProfileBinding::STRENGTH_REQUIRED, $b->getStrength());
        $this->assertTrue($b->isRequired());
        $this->assertSame('http://x/vs|1', $b->getValueSet());
        $this->assertSame('desc', $b->getDescription());

        $e = ProfileElement::fromArray(['id' => 'A.b', 'path' => 'A.b', 'binding' => ['strength' => 'extensible']]);
        $this->assertFalse($e->getBinding()->isRequired());
        $this->assertNull($e->getBinding()->getValueSet());
    }

    public function testSlicing(): void
    {
        $e = ProfileElement::fromArray([
            'id' => 'A.b',
            'path' => 'A.b',
            'slicing' => [
                'discriminator' => [['type' => 'value', 'path' => 'url'], ['type' => 'pattern', 'path' => 'code']],
                'rules' => 'open',
                'ordered' => true,
                'description' => 'by url',
            ],
        ]);
        $s = $e->getSlicing();
        $this->assertSame([['type' => 'value', 'path' => 'url'], ['type' => 'pattern', 'path' => 'code']], $s->getDiscriminators());
        $this->assertSame('open', $s->getRules());
        $this->assertTrue($s->isOrdered());
        $this->assertSame('by url', $s->getDescription());
    }

    public function testSliceElement(): void
    {
        $slice = ProfileElement::fromArray(['id' => 'A.ext:race', 'path' => 'A.ext', 'sliceName' => 'race']);
        $this->assertTrue($slice->isSlice());
        $this->assertTrue($slice->isWithinSlice());
        $this->assertSame('race', $slice->getSliceName());
        $this->assertSame('A', $slice->getParentId());

        $child = ProfileElement::fromArray(['id' => 'A.ext:race.url', 'path' => 'A.ext.url']);
        $this->assertFalse($child->isSlice());
        $this->assertTrue($child->isWithinSlice());
        $this->assertSame('A.ext:race', $child->getParentId());
    }

    public function testConstraintsAndMustSupport(): void
    {
        $e = ProfileElement::fromArray([
            'id' => 'A.b',
            'path' => 'A.b',
            'mustSupport' => true,
            'constraint' => [['key' => 'k-1', 'severity' => 'error', 'human' => 'must be so', 'expression' => 'a.exists()']],
            'contentReference' => '#A.c',
            'short' => 'short text',
            'definition' => 'long text',
        ]);
        $this->assertTrue($e->isMustSupport());
        [$c] = $e->getConstraints();
        $this->assertSame('k-1', $c->getKey());
        $this->assertSame('error', $c->getSeverity());
        $this->assertSame('must be so', $c->getHuman());
        $this->assertSame('a.exists()', $c->getExpression());
        $this->assertSame('#A.c', $e->getContentReference());
        $this->assertSame('short text', $e->getShort());
        $this->assertSame('long text', $e->getDefinition());
    }
}
