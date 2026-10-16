<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);

$imports->addCoreFileImports(
    $typeInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Resolves dotted field paths, such as "contact.relationship", against a root type.
 *
 * Collections encountered along the way are expanded, so a single path may resolve to many parent instances.
 */
class <?php echo $coreFile; ?>

{
    public const SEPARATOR = '.';
    public const CHOICE_SUFFIX = '[x]';

    /**
     * Returns true if the key must be resolved against the type rather than read as a single set property: it has
     * more than one segment, or it names a choice element such as "deceased[x]".
     *
     * @param string $path
     * @return bool
     */
    public static function isPath(string $path): bool
    {
        return str_contains($path, self::SEPARATOR) || str_ends_with($path, self::CHOICE_SUFFIX);
    }

    /**
     * Resolves every instance on which the final segment of $path is defined.
     *
     * Each returned entry is a map with the keys "type" (the parent instance), "field" (the final segment) and
     * "path" (the concrete path from $root, including collection indexes, e.g. "contact.0.relationship").
     * Missing or unset intermediate values resolve to no instances.
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $root
     * @param string $path
     * @return array
     */
    public static function resolveParents(<?php echo $typeInterface; ?> $root, string $path): array
    {
        $segments = explode(self::SEPARATOR, $path);
        $field = array_pop($segments);
        $parents = [['type' => $root, 'path' => '']];
        foreach ($segments as $segment) {
            $next = [];
            foreach ($parents as $parent) {
                $value = $parent['type']->{'get' . $segment}();
                $prefix = '' === $parent['path'] ? $segment : "{$parent['path']}.{$segment}";
                if ($value instanceof <?php echo $typeInterface; ?>) {
                    $next[] = ['type' => $value, 'path' => $prefix];
                } elseif (is_array($value)) {
                    foreach ($value as $i => $vv) {
                        if ($vv instanceof <?php echo $typeInterface; ?>) {
                            $next[] = ['type' => $vv, 'path' => "{$prefix}.{$i}"];
                        }
                    }
                }
            }
            $parents = $next;
        }
        $out = [];
        foreach ($parents as $parent) {
            $out[] = [
                'type' => $parent['type'],
                'field' => $field,
                'path' => '' === $parent['path'] ? $field : "{$parent['path']}.{$field}",
            ];
        }
        return $out;
    }

    /**
     * Unwraps primitive containers until a raw scalar (or null) is reached.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function unwrap(mixed $value): mixed
    {
        while ($value instanceof <?php echo $typeInterface; ?>) {
            $value = $value->getValue();
        }
        return $value;
    }
}
<?php return ob_get_clean();
