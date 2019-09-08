<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var bool $skipImports */
/** @var string $fqns */

// start output buffer
ob_start();

// build php opener
echo "<?php\n\n";

// determine if we need to declare a namespace
$namespace = trim($fqns, PHPFHIR_NAMESPACE_TRIM_CUTSET);
if ('' !== $namespace) {
    echo "namespace {$namespace};\n\n";
}

// print out huge copyright block
echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

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