<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var bool $skipImports */
/** @var string $fqns */

// determine if we need to declare a namespace
$namespace = trim($fqns, PHPFHIR_NAMESPACE_TRIM_CUTSET);

// start output buffer
ob_start();

// build php opener
echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) {
    echo sprintf("namespace %s;\n\n", $namespace);
}

// print out huge copyright block

echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment();

// formatting!
echo "\n\n";

if (!isset($skipImports) || !$skipImports) {
    $imported = 0;
    foreach ($type->getImports() as $import) {
        if ($import->isRequiresImport()) {
            echo $import->getUseStatement();
            $imported++;
        }
    }
    if (0 !== $imported) {
        echo "\n";
    }
}

return ob_get_clean();