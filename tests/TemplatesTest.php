<?php

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

use DCarbone\PHPFHIR\Render\Templates;
use PHPUnit\Framework\TestCase;

class TemplatesTest extends TestCase
{
    /**
     * @param string $rendered
     * @return string
     * @throws \ReflectionException
     */
    private function _pruneUnusedImports(string $rendered): string
    {
        $method = new \ReflectionMethod(Templates::class, 'pruneUnusedImports');
        $method->setAccessible(true);
        return $method->invoke(null, $rendered);
    }

    /**
     * @throws \ReflectionException
     */
    public function testUnreferencedImportIsRemoved(): void
    {
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        use Foo\Bar\Constants;

        class Widget
        {
        }

        PHP;

        $out = $this->_pruneUnusedImports($rendered);
        $this->assertStringNotContainsString('use Foo\Bar\Constants;', $out);
    }

    /**
     * @throws \ReflectionException
     */
    public function testImportReferencedInBodyIsKept(): void
    {
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        use Foo\Bar\FHIRVersion;

        class Widget
        {
            public function go(): FHIRVersion
            {
                return new FHIRVersion();
            }
        }

        PHP;

        $out = $this->_pruneUnusedImports($rendered);
        $this->assertStringContainsString('use Foo\Bar\FHIRVersion;', $out);
    }

    /**
     * @throws \ReflectionException
     */
    public function testAliasedImportRespectsAliasReference(): void
    {
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        use Foo\Bar\Baz as Qux;

        class Widget
        {
            public function go(): Qux
            {
                return new Qux();
            }
        }

        PHP;

        $out = $this->_pruneUnusedImports($rendered);
        $this->assertStringContainsString('use Foo\Bar\Baz as Qux;', $out);
    }

    /**
     * @throws \ReflectionException
     */
    public function testAliasedImportRemovedWhenAliasUnreferenced(): void
    {
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        use Foo\Bar\Baz as Qux;

        class Widget
        {
        }

        PHP;

        $out = $this->_pruneUnusedImports($rendered);
        $this->assertStringNotContainsString('use Foo\Bar\Baz as Qux;', $out);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSimilarlyNamedIdentifierDoesNotFalselyKeepImport(): void
    {
        // "getVersion()" must not be mistaken for a reference to the "Version" import.
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        use Foo\Bar\Version;

        class Widget
        {
            public function getVersion(): string
            {
                return 'v1';
            }
        }

        PHP;

        $out = $this->_pruneUnusedImports($rendered);
        $this->assertStringNotContainsString('use Foo\Bar\Version;', $out);
    }

    /**
     * @throws \ReflectionException
     */
    public function testMultipleImportsPrunedIndependently(): void
    {
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        use Foo\Bar\Constants;
        use Foo\Bar\FHIRVersion;

        class Widget
        {
            public function go(): FHIRVersion
            {
                return new FHIRVersion();
            }
        }

        PHP;

        $out = $this->_pruneUnusedImports($rendered);
        $this->assertStringNotContainsString('use Foo\Bar\Constants;', $out);
        $this->assertStringContainsString('use Foo\Bar\FHIRVersion;', $out);
    }

    /**
     * @throws \ReflectionException
     */
    public function testNoImportsReturnsInputUnchanged(): void
    {
        $rendered = <<<'PHP'
        <?php declare(strict_types=1);

        namespace Foo;

        class Widget
        {
        }

        PHP;

        $this->assertSame($rendered, $this->_pruneUnusedImports($rendered));
    }
}
