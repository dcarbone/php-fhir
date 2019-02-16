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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// localize logger so we don't have to call "$config->getLogger()" all over the place...
$log = $config->getLogger();

$fqns = $type->getFullyQualifiedNamespace(false);
if (false === NameUtils::isValidNSName($fqns)) {
    throw ExceptionUtils::createInvalidTypeNamespaceException($type);
}

$typeClassName = $type->getClassName();
if (false === NameUtils::isValidClassName($typeClassName)) {
    throw ExceptionUtils::createInvalidTypeClassNameException($type);
}

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

// next, begin use statement compilation

// store so we can sort them before output
$imports = [];

// determine if we need to import our parent type
if ($parentType = $type->getParentType()) {
    if ($parentType->getFullyQualifiedNamespace(false) !== $fqns) {
        $imports[] = $parentType->getFullyQualifiedClassName(false);
    }
}

// next, figure out property types to import
foreach ($type->getProperties()->getIterator() as $property) {
    // we need only concern ourselves with type'd properties for now
    if ($propertyType = $property->getValueFHIRType()) {
        if ($propertyType->getFullyQualifiedNamespace(false) !== $fqns) {
            $imports[] = $propertyType->getFullyQualifiedNamespace(false);
        }
    }
}

// finally, sort and then print the imports
$imports = array_unique($imports);
natcasesort($imports);

foreach ($imports as $import) {
    echo "use {$import};\n";
}

echo "\n";

return ob_get_clean();