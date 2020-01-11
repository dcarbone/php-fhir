<?php
/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$typeKind = $type->getKind();

$bundleType = null;
// only bother to locate bundle type if there is a configured test endpoint
if (null !== $config->getTestEndpoint() && $type->isDomainResource()) :
    // TODO: find a more efficient way to do this...
    foreach ($types->getIterator() as $bt) :
        if ($bt->getFHIRName() === 'Bundle') :
            $bundleType = $bt;
            break;
        endif;
    endforeach;
    if (null === $bundleType) :
        throw ExceptionUtils::createBundleTypeNotFoundException($type);
    endif;
endif;

ob_start();

echo require_with(
    PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . '/class_header.php',
    [
        'config'     => $config,
        'type'       => $type,
        'bundleType' => $bundleType,
    ]
);

echo require_with(
    PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . '/default_body.php',
    [
        'config' => $config,
        'type'   => $type,
    ]
);

if (null !== $config->getTestEndpoint() && $type->isDomainResource()) :
    echo require_with(
        PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . '/methods/domain_resource.php',
        [
            'config'     => $config,
            'type'       => $type,
            'bundleType' => $bundleType,
        ]
    );
endif;

if ($typeKind->isPrimitive()) :
    echo require_with(
        PHPFHIR_TEMPLATE_TESTS_TYPES_DIR . '/methods/primitive.php',
        [
            'config' => $config,
            'type' => $type,
        ]
    );
endif;

echo "}\n";
return ob_get_clean();
