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

use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DocumentationUtilsTest extends TestCase
{
    public static function commentTextProvider(): array
    {
        return [
            'plain' => ['https://example.org/fhir/patient', 'https://example.org/fhir/patient'],
            'terminator' => ['https://example.org/*/patient', 'https://example.org/* /patient'],
            'repeated star' => ['a **/ b', 'a ** / b'],
            'adjacent terminators' => ['*/*/', '* /* /'],
            'opener only' => ['a /* b', 'a /* b'],
            'php close tag' => ['a ?> b', 'a ?> b'],
        ];
    }

    #[DataProvider('commentTextProvider')]
    public function testEscapeCommentText(string $text, string $expected): void
    {
        $this->assertSame($expected, DocumentationUtils::escapeCommentText($text));
        $this->assertSame($expected, DocumentationUtils::escapeCommentLine($text));
    }

    public function testEscapeCommentTextKeepsLineBreaks(): void
    {
        $this->assertSame("a\n* /b", DocumentationUtils::escapeCommentText("a\n*/b"));
    }

    public function testEscapeCommentLineCollapsesLineBreaks(): void
    {
        $this->assertSame('a * / b c', DocumentationUtils::escapeCommentLine("  a *\r\n/ b \n\n c "));
    }

    public static function injectionProvider(): array
    {
        return [
            'terminator' => ['x */ $GLOBALS[\'injected\'] = true; /* y'],
            'terminator across line break' => ["x *\n/ \$GLOBALS['injected'] = true; /* y"],
            'repeated star' => ['x **/ $GLOBALS[\'injected\'] = true; /* y'],
        ];
    }

    /**
     * Whatever the text, the comment it is written into must remain a single comment token.
     */
    #[DataProvider('injectionProvider')]
    public function testEscapedTextCannotLeaveTheComment(string $text): void
    {
        foreach ([DocumentationUtils::escapeCommentText($text), DocumentationUtils::escapeCommentLine($text)] as $escaped) {
            $tokens = token_get_all("<?php\n/**\n * {$escaped}\n */\n", TOKEN_PARSE);
            $this->assertCount(3, $tokens);
            $this->assertSame(T_DOC_COMMENT, $tokens[1][0]);
        }
    }
}
