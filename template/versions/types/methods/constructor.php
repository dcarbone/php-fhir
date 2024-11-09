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

use DCarbone\PHPFHIR\Enum\TypeKind;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Property[] $properties */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type|null $parentType */

$typeKind = $type->getKind();
$requireArgs = [
    'config' => $config,
    'version' => $version,
    'type' => $type,
    'parentType' => $parentType,
    'properties' => $properties,
];

ob_start();

echo match ($typeKind) {
    TypeKind::PRIMITIVE, TypeKind::LIST => require_with(PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'primitive.php', $requireArgs),
    TypeKind::PRIMITIVE_CONTAINER => require_with(PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'primitive_container.php', $requireArgs),
    default => require_with(PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR  . DIRECTORY_SEPARATOR . 'default.php', $requireArgs),
};

echo "\n";

return ob_get_clean();