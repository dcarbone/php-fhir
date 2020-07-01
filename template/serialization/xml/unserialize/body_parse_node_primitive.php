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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propType = $property->getValueFHIRType();

ob_start(); ?>
            $valueAttr = $n->attributes->getNamedItem('value');
            if (null !== $valueAttr) {
                $type->setValue($valueAttr->nodeValue);
            } elseif ($n->hasChildNodes()) {
                $type->setValue($n->ownerDocument->saveXML($n));
            } else {
                $type->setValue($n->textContent);
            }
<?php if (null !== $propType && $propType->getKind()->is(TypeKindEnum::RAW)) : ?>
            $type->_setElementName($n->nodeName);
<?php endif;
return ob_get_clean();