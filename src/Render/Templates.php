<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Render;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
        return self::normalize(require $coreFile->getTemplateFile());
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionXHTMLTypeClass(Version $version, Type $type): string
    {
        return self::normalize(require sprintf('%s/class_xhtml.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClass(Version $version, Type $type): string
    {
        if ($type->getKind()->isResourceContainer($version)) {
            return self::normalize(require sprintf('%s/class_resource_container.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
        } else if ($type->isPrimitiveType() && !$type->hasPrimitiveTypeParent()) {
            return self::normalize(require sprintf('%s/class_primitive.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
        }
        return self::normalize(require sprintf('%s/class_default.php', PHPFHIR_TEMPLATE_VERSION_TYPES_DIR));
    }

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return string
     */
    public static function renderVersionTypeClassTest(Version $version, Type $type): string
    {
        return self::normalize(require sprintf('%s/class.php', PHPFHIR_TEMPLATE_TESTS_VERSIONS_TYPES_DIR));
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
        $rendered = self::pruneUnusedImports($rendered);
        $rendered = preg_replace('/[ \t]+$/m', '', $rendered);
        return rtrim($rendered, "\r\n") . "\n";
    }

    /**
     * Drop "use" import statements that go unreferenced in the rendered file body.
     *
     * Imports are added speculatively per type-category by {@see \DCarbone\PHPFHIR\Utilities\ImportUtils}
     * (e.g. "every resource type needs Constants"), so a given type's rendered body frequently
     * never ends up referencing an import that was added on spec. Rather than teaching every
     * template to conditionally emit each "use" line based on what its body will contain, this
     * scans the fully rendered file text once, after everything (imports and class body alike)
     * has already been rendered to string: for each imported symbol, its local name (explicit
     * alias, or the class/interface/trait's short name) is searched for elsewhere in the file.
     * Imports with zero remaining references are dropped.
     *
     * This is deliberately a lightweight regex heuristic rather than full AST/tokenizer analysis:
     * it only removes an import when its local name has zero word-boundary matches anywhere else
     * in the file (type hints, "instanceof", "::class", "new", docblocks, etc. all count as a
     * match), so it can never strip an import that is genuinely referenced. The only failure mode
     * is *not* removing an import that's referenced through some construct this can't recognize -
     * a false negative that just leaves a harmless unused import in place, never a false positive
     * that breaks the class.
     *
     * @param string $rendered
     * @return string
     */
    private static function pruneUnusedImports(string $rendered): string
    {
        if (!preg_match_all(
            '/^use\s+([^\s;]+)(?:\s+as\s+(\S+))?;\r?\n/m',
            $rendered,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return $rendered;
        }

        $remove = [];
        foreach ($matches[0] as $i => [$useStatement, $offset]) {
            $fqn = $matches[1][$i][0];
            $alias = $matches[2][$i][0];

            if ('' !== $alias) {
                $localName = $alias;
            } else {
                $lastSeparator = strrpos($fqn, PHPFHIR_NAMESPACE_SEPARATOR);
                $localName = false === $lastSeparator ? $fqn : substr($fqn, $lastSeparator + 1);
            }

            // search everywhere in the file _except_ this specific "use" line for a reference
            // to the imported symbol's local name.
            $haystack = substr($rendered, 0, $offset) . substr($rendered, $offset + strlen($useStatement));

            if (0 === preg_match('/\b' . preg_quote($localName, '/') . '\b/', $haystack)) {
                $remove[] = $useStatement;
            }
        }

        if ([] === $remove) {
            return $rendered;
        }

        return str_replace($remove, '', $rendered);
    }
}
