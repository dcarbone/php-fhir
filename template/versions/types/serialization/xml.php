<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type; */

$sourceMeta = $version->getSourceMetadata();

$typeKind = $type->getKind();

ob_start();

// unserialize portion
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/xml/unserialize/header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);

if (count($type->getAllPropertiesIndexedIterator()) > 0) :
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/xml/unserialize/body.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );
endif;
?>
        return $type;
    }
<?php
// start xml serialize
if ($type->hasLocalProperties()
    && ($type->isResourceType() || $type->hasResourceTypeParent() || $type->hasNonOverloadedProperties())) :

    echo "\n";

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/xml/serialize/header.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/xml/serialize/body.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );

    if ($type->isResourceType() || $type->hasResourceTypeParent() || $sourceMeta->isDSTU1()) : ?>
        if (isset($rootOpened) && $rootOpened) {
            $xw->endElement();
        }
        if (isset($docStarted) && $docStarted) {
            $xw->endDocument();
        }
        return $xw;
<?php
    endif; ?>
    }
<?php
    // end xml serialize
endif;

return ob_get_clean();
