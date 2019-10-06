<?php namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    const _INCLUDE = 'include';
    const IMPORT   = 'import';

    const COMPLEX_TYPE    = 'complexType';
    const COMPLEX_CONTENT = 'complexContent';

    const SIMPLE_TYPE    = 'simpleType';
    const SIMPLE_CONTENT = 'simpleContent';

    const ANNOTATION    = 'annotation';
    const DOCUMENTATION = 'documentation';
    const RESTRICTION   = 'restriction';
    const EXTENSION     = 'extension';

    const ATTRIBUTE   = 'attribute';
    const SEQUENCE    = 'sequence';
    const UNION       = 'union';
    const ELEMENT     = 'element';
    const CHOICE      = 'choice';
    const ENUMERATION = 'enumeration';

    const  MIN_LENGTH = 'minLength';
    const  MAX_LENGTH = 'maxLength';
    const  PATTERN    = 'pattern';

    const ANY = 'any';
}