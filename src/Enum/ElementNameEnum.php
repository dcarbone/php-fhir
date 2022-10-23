<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class ElementNameEnum
 * @package DCarbone\PHPFHIR\Enum
 */
class ElementNameEnum extends AbstractEnum
{
    public const _INCLUDE = 'include';
    public const IMPORT   = 'import';

    public const COMPLEX_TYPE    = 'complexType';
    public const COMPLEX_CONTENT = 'complexContent';

    public const SIMPLE_TYPE    = 'simpleType';
    public const SIMPLE_CONTENT = 'simpleContent';

    public const ANNOTATION    = 'annotation';
    public const DOCUMENTATION = 'documentation';
    public const RESTRICTION   = 'restriction';
    public const EXTENSION     = 'extension';

    public const ATTRIBUTE   = 'attribute';
    public const SEQUENCE    = 'sequence';
    public const UNION       = 'union';
    public const ELEMENT     = 'element';
    public const CHOICE      = 'choice';
    public const ENUMERATION = 'enumeration';

    public const MIN_LENGTH = 'minLength';
    public const MAX_LENGTH = 'maxLength';
    public const PATTERN    = 'pattern';

    public const ANY = 'any';
}