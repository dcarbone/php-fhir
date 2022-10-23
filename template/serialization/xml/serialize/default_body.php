<?php declare(strict_types=1);

/*
 * Copyright 2018-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $directProperties */

ob_start();
foreach ($directProperties as $property) :
    if (null !== $property->getValueFHIRType()) :
        echo require_with(
            __DIR__ . '/default_body_typed.php',
            [
                'property' => $property,
            ]
        );
    elseif (null === $parentType) :
        echo require_with(__DIR__ . '/default_body_untyped.php', []);
    endif;
endforeach;
return ob_get_clean();
