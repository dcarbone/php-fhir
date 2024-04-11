<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
enum ElementName : string
{
    use EnumCompat;

    case _INCLUDE = 'include';
    case IMPORT   = 'import';

    case COMPLEX_TYPE    = 'complexType';
    case COMPLEX_CONTENT = 'complexContent';

    case SIMPLE_TYPE    = 'simpleType';
    case SIMPLE_CONTENT = 'simpleContent';

    case ANNOTATION    = 'annotation';
    case DOCUMENTATION = 'documentation';
    case RESTRICTION   = 'restriction';
    case EXTENSION     = 'extension';

    case ATTRIBUTE   = 'attribute';
    case SEQUENCE    = 'sequence';
    case UNION       = 'union';
    case ELEMENT     = 'element';
    case CHOICE      = 'choice';
    case ENUMERATION = 'enumeration';

    case MIN_LENGTH = 'minLength';
    case MAX_LENGTH = 'maxLength';
    case PATTERN    = 'pattern';

    case ANY = 'any';

    /**
     * @param \DCarbone\PHPFHIR\Enum\ElementName|string ...$other
     * @return bool
     */
    public function isOneOf(ElementName|string ...$other): bool
    {
        $vals = self::values();
        foreach ($other as $name) {
            if ($this === $name || in_array($name, $vals, true)) {
                return true;
            }
        }

        return false;
    }
}