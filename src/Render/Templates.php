<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Render;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\CoreFile;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;

/**
 * Class TemplateBuilder
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class Templates
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\CoreFile $coreFile
     * @param array $kwargs
     * @return string
     */
    public static function renderCoreFile(Config $config, CoreFile $coreFile, array $kwargs): string
    {
        extract($kwargs);
        return self::finalize(require $coreFile->getTemplateFile());
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionXHTMLTypeClass(Version $version, Type $type): string
    {
        return self::finalize(require sprintf('%s/class_xhtml.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClass(Version $version, Type $type): string
    {
        if ($type->getKind()->isResourceContainer($version)) {
            return self::finalize(require sprintf('%s/class_resource_container.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
        } else if ($type->isPrimitiveType() && !$type->hasPrimitiveTypeParent()) {
            return self::finalize(require sprintf('%s/class_primitive.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
        }
        return self::finalize(require sprintf('%s/class_default.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClassTest(Version $version, Type $type): string
    {
        return self::finalize(require sprintf('%s/class.php', PHPFHIR_TEMPLATE_TESTS_VERSIONS_TYPES_DIR));
    }

    /**
     * Post-process rendered template output at the single render chokepoint.
     *
     * @param string $rendered
     * @return string
     */
    private static function finalize(string $rendered): string
    {
        return self::normalize(self::removeUnusedImports($rendered));
    }

    /**
     * Normalize whitespace in rendered template output.
     *
     * Templates are PHP files rendered via output buffering, so indented inline control
     * structures (e.g. "    <?php endif; ?>") emit their leading indentation as whitespace-only
     * or trailing-whitespace lines. Normalizing here, at the single render chokepoint, keeps
     * every generated file free of trailing whitespace and ending in exactly one newline,
     * without editing each template.
     *
     * @param string $rendered
     * @return string
     */
    private static function normalize(string $rendered): string
    {
        $rendered = preg_replace('/[ \t]+$/m', '', $rendered);
        return rtrim($rendered, "\r\n") . "\n";
    }

    /**
     * Drop top-level `use` imports the rendered body never references.
     *
     * Imports are added speculatively per type-category in ImportUtils, so a given type can carry
     * imports (e.g. Constants, FHIRVersion) its body never uses. The header renders the import block
     * before the body exists, so it cannot know which are actually referenced; stripping here, where
     * the whole file is available, removes only imports whose imported name (alias or final segment)
     * appears nowhere else in the file -- as a code identifier or inside a doc comment. An import that
     * is the sole occurrence of its name is therefore safe to drop.
     *
     * @param string $rendered
     * @return string
     */
    private static function removeUnusedImports(string $rendered): string
    {
        $tokens = token_get_all($rendered);
        $count = count($tokens);

        // Collect top-level `use` statements: their imported short name and the token range spanning
        // `use ...;`, used both to skip them when tallying usage and to find the line to excise.
        $useStatements = [];
        $braceDepth = 0;
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === '{') {
                $braceDepth++;
                continue;
            }
            if ($token === '}') {
                $braceDepth--;
                continue;
            }
            if ($braceDepth !== 0 || !is_array($token) || $token[0] !== T_USE) {
                continue;
            }

            $fqn = '';
            $alias = null;
            $seenAs = false;
            $isFuncOrConst = false;
            $j = $i + 1;
            while ($j < $count && $tokens[$j] !== ';') {
                $inner = $tokens[$j];
                if (is_array($inner)) {
                    if ($inner[0] === T_FUNCTION || $inner[0] === T_CONST) {
                        $isFuncOrConst = true;
                    } else if ($inner[0] === T_AS) {
                        $seenAs = true;
                    } else if ($inner[0] === T_STRING || $inner[0] === T_NAME_QUALIFIED || $inner[0] === T_NAME_FULLY_QUALIFIED) {
                        if ($seenAs) {
                            $alias = $inner[1];
                        } else {
                            $fqn .= $inner[1];
                        }
                    } else if ($inner[0] === T_NS_SEPARATOR) {
                        $fqn .= '\\';
                    }
                }
                $j++;
            }

            // Only namespace imports of a single symbol are handled. `use function`/`use const` and
            // grouped imports are left untouched (the generator emits neither).
            if (!$isFuncOrConst && $fqn !== '' && str_contains($fqn, '\\')) {
                $short = $alias ?? substr($fqn, strrpos($fqn, '\\') + 1);
                $useStatements[] = [
                    'short' => $short,
                    'startToken' => $i,
                    'endToken' => $j,
                ];
            }

            $i = $j;
        }

        if ($useStatements === []) {
            return $rendered;
        }

        $useTokenIndexes = [];
        foreach ($useStatements as $stmt) {
            for ($k = $stmt['startToken']; $k <= $stmt['endToken']; $k++) {
                $useTokenIndexes[$k] = true;
            }
        }

        // Tally how often each imported short name appears outside the use statements, counting both
        // code identifiers and doc-comment references (PHP-CS-Fixer's no_unused_imports honors both).
        $usage = [];
        foreach ($useStatements as $stmt) {
            $usage[$stmt['short']] = $usage[$stmt['short']] ?? 0;
        }
        for ($i = 0; $i < $count; $i++) {
            if (isset($useTokenIndexes[$i])) {
                continue;
            }
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }
            if ($token[0] === T_STRING) {
                if (isset($usage[$token[1]])) {
                    $usage[$token[1]]++;
                }
            } else if ($token[0] === T_NAME_QUALIFIED) {
                $first = substr($token[1], 0, strpos($token[1], '\\'));
                if (isset($usage[$first])) {
                    $usage[$first]++;
                }
            } else if ($token[0] === T_DOC_COMMENT || $token[0] === T_COMMENT) {
                foreach ($usage as $short => $_) {
                    if (preg_match('/\b' . preg_quote($short, '/') . '\b/', $token[1]) === 1) {
                        $usage[$short]++;
                    }
                }
            }
        }

        // Each unused import occupies its own line; drop those lines by their 1-based token line number.
        $lines = explode("\n", $rendered);
        $dropLines = [];
        foreach ($useStatements as $stmt) {
            if ($usage[$stmt['short']] !== 0) {
                continue;
            }
            $lineNo = $tokens[$stmt['startToken']][2] ?? null;
            if ($lineNo !== null) {
                $dropLines[$lineNo - 1] = true;
            }
        }

        if ($dropLines === []) {
            return $rendered;
        }

        $kept = [];
        foreach ($lines as $idx => $line) {
            if (!isset($dropLines[$idx])) {
                $kept[] = $line;
            }
        }

        return implode("\n", $kept);
    }
}
