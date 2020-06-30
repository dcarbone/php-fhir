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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$directProperties = $type->getProperties()->getDirectIterator();

ob_start(); ?>
        for($i = 0; $i < $element->childNodes->length; $i++) {
            $n = $element->childNodes->item($i);
            if (!($n instanceof \DOMElement)) {
                continue;
            }
<?php foreach ($directProperties as $property) :
    if (null !== $property->getValueFHIRType()) :
        echo require_with(
            __DIR__ . '/body_parse_node_typed.php',
            [
                'property' => $property,
            ]
        );
    else :
        echo require_with(
            __DIR__ . '/body_parse_node_primitive.php',
            [
                'property' => $property,
            ]
        );
    endif;
endforeach; ?>
        }
<?php foreach ($directProperties as $property) :
    if (null !== $property->getValueFHIRType()) :
        echo require_with(
            __DIR__ . '/body_parse_attr_typed.php',
            [
                'property' => $property,
            ]
        );
    else :
        echo require_with(
            __DIR__ . '/body_parse_attr_primitive.php',
            [
                'property' => $property,
            ]
        );
    endif;
endforeach;
return ob_get_clean();