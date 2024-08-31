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
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TestType $testType */

$typeKind = $type->getKind();

ob_start();

echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPE_TESTS_DIR . DIRECTORY_SEPARATOR . $testType->value . DIRECTORY_SEPARATOR . 'header.php',
    [
        'config'     => $config,
        'type'       => $type,
    ]
);

echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPE_TESTS_DIR . DIRECTORY_SEPARATOR . $testType->value . DIRECTORY_SEPARATOR . 'body_base.php',
    [
        'config' => $config,
        'type'   => $type,
    ]
);

if ($typeKind === TypeKind::PRIMITIVE) {
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPE_TESTS_DIR . DIRECTORY_SEPARATOR . $testType->value . DIRECTORY_SEPARATOR . 'body_primitive.php',
        [
            'config' => $config,
            'type' => $type,
        ]
    );
}

echo "}\n";
return ob_get_clean();
