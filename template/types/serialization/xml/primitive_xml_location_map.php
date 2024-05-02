<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $localProperties */

ob_start(); ?>
    /** @var array */
    private array $_primitiveXmlLocations = [<?php
foreach($localProperties as $p) :
    if ($p->isCollection()) {
        continue;
    }
    $pt = $p->getValueFHIRType();
    if (null === $pt) : ?>

        self::FIELD_VALUE => <?php echo PHPFHIR_ENUM_XML_SERIALIZE_LOCATION_ENUM; ?>::ATTRIBUTE,<?php
    elseif ($pt->hasPrimitiveParent() || $pt->getKind() == TypeKind::PRIMITIVE) : ?>

        self::<?php echo $p->getFieldConstantName(); ?> => <?php echo PHPFHIR_ENUM_XML_SERIALIZE_LOCATION_ENUM; ?>::ATTRIBUTE,<?php
    endif;
endforeach; ?>

    ];
<?php return ob_get_clean();