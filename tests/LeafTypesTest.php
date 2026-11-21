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
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Version\Definition\Property;
use DCarbone\PHPFHIR\Version\Definition\Type;
use DCarbone\PHPFHIR\Version\Definition\TypeDecorator;
use DCarbone\PHPFHIR\Version\Definition\Types;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class LeafTypesTest extends TestCase
{
    private TemporaryDirectory $_tmpdir;
    private Config $_config;

    protected function setUp(): void
    {
        $this->_tmpdir = new TemporaryDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'LeafTypesTest');
        $this->_tmpdir->create();
        $this->_config = Config::fromArray([
            'libraryPath' => $this->_tmpdir->path(),
            'libraryNamespacePrefix' => 'LeafTypesRegression',
            'versions' => [['name' => 'R4', 'schemaPath' => $this->_tmpdir->path()]],
        ]);
    }

    protected function tearDown(): void
    {
        $this->_tmpdir->delete();
    }

    public function testLeafPredicateAndCommentFlagsMatchGeneratedTypeRoles(): void
    {
        $version = $this->_config->getVersion('R4');
        $types = new Types($version);
        $primitive = (new Type($version, 'string-primitive'))->setKind(TypeKindEnum::PRIMITIVE);
        $derived = (new Type($version, 'code-list'))->setKind(TypeKindEnum::LIST)->setParentType($primitive);
        $descendant = (new Type($version, 'custom-code'))->setKind(TypeKindEnum::LIST)->setParentType($derived);
        $element = (new Type($version, 'Element'))->setKind(TypeKindEnum::ELEMENT);
        $container = (new Type($version, 'string'))->setKind(TypeKindEnum::ELEMENT)
            ->setParentType($element)->setPrimitiveContainer(true);
        $containerChild = (new Type($version, 'custom-string'))->setKind(TypeKindEnum::ELEMENT)
            ->setParentType($container);
        $resource = (new Type($version, 'Resource'))->setKind(TypeKindEnum::RESOURCE);

        foreach ([$primitive, $derived, $descendant, $element, $container, $containerChild, $resource] as $type) {
            $types->addOrReturnType($type);
        }
        TypeDecorator::setCommentContainerFlag($this->_config, $types);

        $xhtml = $types->getTypeByName(PHPFHIR_XHTML_TYPE_NAME);
        self::assertNotNull($xhtml);
        foreach ([$primitive, $derived, $descendant, $xhtml] as $type) {
            self::assertTrue($type->isLeafType(), $type->getFHIRName());
            self::assertFalse($type->isCommentContainer(), $type->getFHIRName());
        }
        foreach ([$element, $container, $containerChild, $resource] as $type) {
            self::assertFalse($type->isLeafType(), $type->getFHIRName());
            self::assertTrue($type->isCommentContainer(), $type->getFHIRName());
        }
        self::assertArrayHasKey(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE, $primitive->getDirectlyImplementedInterfaces());
        self::assertArrayHasKey(PHPFHIR_TYPES_INTERFACE_LEAF_TYPE, $xhtml->getDirectlyImplementedInterfaces());
    }

    public function testGeneratedMarkerPreservesPrimitiveAndXHTMLContracts(): void
    {
        (new Builder($this->_config))->writeLibraryCoreEntities();
        $coreFiles = $this->_config->getCoreFiles();
        $autoloaderFile = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_AUTOLOADER);
        $autoloader = $autoloaderFile->getFullyQualifiedName(false);
        require $autoloaderFile->getFilepath();

        try {
            $leaf = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_LEAF_TYPE)->getFullyQualifiedName(false);
            $primitive = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE)->getFullyQualifiedName(false);
            $valueContainer = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_VALUE_CONTAINER_TYPE)->getFullyQualifiedName(false);
            $typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE)->getFullyQualifiedName(false);
            self::assertTrue(is_a($leaf, $typeInterface, true));
            self::assertTrue(is_a($primitive, $leaf, true));
            self::assertTrue(is_a($primitive, $valueContainer, true));
            self::assertFalse(is_a($leaf, $valueContainer, true));
            self::assertEquals(
                (new \ReflectionClass($typeInterface))->getMethods(),
                (new \ReflectionClass($leaf))->getMethods(),
            );

            $version = $this->_config->getVersion('R4');
            $xhtml = (new Type($version, PHPFHIR_XHTML_TYPE_NAME))->setKind(TypeKindEnum::PHPFHIR_XHTML);
            $path = $this->_tmpdir->path('FHIRXHTML.php');
            file_put_contents($path, Templates::renderVersionXHTMLTypeClass($version, $xhtml));
            require $path;

            $class = $xhtml->getFullyQualifiedClassName(false);
            $markup = '<div xmlns="http://www.w3.org/1999/xhtml"><p>Leaf content</p></div>';
            $value = new $class($markup);
            self::assertInstanceOf($leaf, $value);
            self::assertNotInstanceOf($primitive, $value);
            self::assertNotInstanceOf($valueContainer, $value);
            self::assertSame($markup, $value->getValue());
            self::assertSame($markup, json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
            self::assertSame($markup, (string)$value);
            $document = $value->getDOMDocument(0);
            self::assertNotNull($document);
            self::assertSame($markup, $document->saveXML($document->documentElement));
            $value->setValue(null);
            self::assertNull($value->getValue());
            self::assertSame('null', json_encode($value, JSON_THROW_ON_ERROR));
        } finally {
            $autoloader::unregister();
        }
    }

    public function testJSONUnserializeUsesTheSameLeafBranchForXHTMLAndPrimitives(): void
    {
        $version = $this->_config->getVersion('R4');
        $type = (new Type($version, 'Narrative'))->setKind(TypeKindEnum::ELEMENT);
        $xhtml = (new Type($version, PHPFHIR_XHTML_TYPE_NAME))->setKind(TypeKindEnum::PHPFHIR_XHTML);
        $primitive = (new Type($version, 'string-primitive'))->setKind(TypeKindEnum::PRIMITIVE);
        foreach (['div' => $xhtml, 'value' => $primitive] as $name => $propertyType) {
            $type->getProperties()->addOrReturnProperty(new Property(
                $type,
                new \SimpleXMLElement('<element/>'),
                '',
                name: $name,
                valueFHIRType: $propertyType,
            ));
        }
        $rendered = require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/json/unserialize/body.php',
            ['version' => $version, 'type' => $type],
        );
        self::assertSame(1, substr_count($rendered, '$type->setDiv($decoded->div);'));
        self::assertSame(1, substr_count($rendered, '$type->setValue($decoded->value);'));
        self::assertStringNotContainsString('jsonUnserialize(', $rendered);
    }
}
