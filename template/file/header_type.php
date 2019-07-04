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

if (!isset($skipImports) || !$skipImports) {
    // next, begin use statement compilation

    // store so we can sort them before output
    $classImports = [];

    $typeNS = $type->getFullyQualifiedNamespace(false);
    $configNS = $config->getNamespace(false);

    if ($typeNS !== $configNS) {
        if ($type->isContainedType()) {
            $classImports[] = "{$configNS}\\" . PHPFHIR_INTERFACE_CONTAINED_TYPE;
        }
        $classImports[] = "{$configNS}\\" . PHPFHIR_INTERFACE_TYPE;
        $classImports[] = "{$configNS}\\" . PHPFHIR_CLASSNAME_CONSTANTS;
    }

    // determine if we need to import our parent type
    if ($parentType = $type->getParentType()) {
        if ($parentType->getFullyQualifiedNamespace(false) !== $typeNS) {
            $classImports[] = $parentType->getFullyQualifiedClassName(false);
        }
    }

    // determine if we need to import a restriction base
    if ($restrictionBaseType = $type->getRestrictionBaseFHIRType()) {
        if ($restrictionBaseType->getFullyQualifiedNamespace(false) !== $typeNS) {
            $classImports[] = $restrictionBaseType->getFullyQualifiedClassName(false);
        }
    }

    // add property types to import statement
    foreach ($sortedProperties as $property) {
        $propertyType = $property->getValueFHIRType();
        if ($propertyType->getKind()->isOneOf([TypeKindEnum::RESOURCE_CONTAINER, TypeKindEnum::RESOURCE_INLINE]) &&
            $typeNS !== $configNS) {
            $classImports[] = "{$configNS}\\" . PHPFHIR_INTERFACE_CONTAINED_TYPE;
            $classImports[] = "{$configNS}\\" . PHPFHIR_CLASSNAME_TYPEMAP;
        } else {
            $propertyTypeNS = $propertyType->getFullyQualifiedNamespace(false);
            if ($propertyTypeNS === $typeNS) {
                continue;
            }
            $classImports[] = $propertyType->getFullyQualifiedClassName(false);
        }
    }

    // finally, sort and then print the imports
    $classImports = array_unique($classImports);
    natcasesort($classImports);
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

if (!isset($skipImports) || !$skipImports) {
    foreach ($classImports as $import) {
        echo "use {$import};\n";
    }

    if ([] !== $classImports) {
        echo "\n";
    }
}

return ob_get_clean();