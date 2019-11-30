<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */

$typeKind = $type->getKind();
$requireArgs = [
    'type'             => $type,
    'parentType'       => $parentType,
    'sortedProperties' => $sortedProperties,
];

ob_start();

switch (true) :
    case $type->hasPrimitiveParent():
        // types that are just primitive extensions do not get their own constructor.
        break;
    case $typeKind->isPrimitive():
        echo require_with(PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/primitive.php', $requireArgs);
        break;
    case $typeKind->isPrimitiveContainer():
        echo require_with(PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/primitive_container.php', $requireArgs);
        break;
    case ($type->isValueContainer() || $type->hasValueContainerParent()):
        echo require_with(PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/value_container.php', $requireArgs);
        break;

    default:
        echo require_with(PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/default.php', $requireArgs);
endswitch;

echo "\n";

return ob_get_clean();