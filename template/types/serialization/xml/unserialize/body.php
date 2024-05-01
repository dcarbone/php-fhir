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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $properties */

$requireArgs = [
    'config' => $config,
];

ob_start(); ?>
        foreach ($element->children() as $n) {
            $childName = $n->getName();
<?php foreach ($properties as $i => $property) {
    if (null !== $property->getValueFHIRType()) {
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_node_typed.php',
            $requireArgs + [
                'property' => $property,
                'i' => $i,
            ]
        );
    } else {
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_node_primitive.php',
            $requireArgs + [
                'property' => $property,
                'i' => $i,
            ]
        );
    }
}; ?>

        }
<?php foreach ($properties as $i => $property) {
    if (null !== $property->getValueFHIRType()) {
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_attr_typed.php',
            $requireArgs + [
                'property' => $property,
                'i' => $i,
            ]
        );
    } else {
        echo require_with(
            __DIR__ . DIRECTORY_SEPARATOR . 'body_parse_attr_primitive.php',
            $requireArgs + [
                'property' => $property,
                'i' => $i,
            ]
        );
    }
}
return ob_get_clean();