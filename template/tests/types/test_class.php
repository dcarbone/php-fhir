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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

ob_start();

echo require_with(
    PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . '/class_header.php',
    [
        'config' => $config,
        'type'   => $type,
    ]
);

echo require_with(
    PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . '/default_body.php',
    [
        'config' => $config,
        'type'   => $type,
    ]
);

echo "}\n";
return ob_get_clean();