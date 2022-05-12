<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Definition\Property;

/**
 * Class DocumentationUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class DocumentationUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @param int $spaces
     * @param bool $trailingNewline
     * @return string
     */
    public static function compilePropertyDocumentation(Property $property, int $spaces, bool $trailingNewline): string
    {
        $propValueType = $property->getValueFHIRType();

        $typeDoc = null === $propValueType ?
            '' :
            trim($propValueType->getDocBlockDocumentationFragment($spaces, false));
        $propDoc = trim($property->getDocBlockDocumentationFragment($spaces, false));

        if ('' === $typeDoc && '' === $propDoc) {
            return '';
        }

        $out = '';
        if ('' !== $typeDoc && '' !== $propDoc) {
            $out .= $typeDoc . "\n" . str_repeat(' ', $spaces) . "*\n" . str_repeat(' ', $spaces) . $propDoc;
        } elseif ('' !== $typeDoc) {
            $out .= $typeDoc;
        } elseif ('' !== $propDoc) {
            $out .= $propDoc;
        }
        return '' === $out ? '' : (str_repeat(' ', $spaces) . $out . ($trailingNewline ? "\n" : ''));
    }
}