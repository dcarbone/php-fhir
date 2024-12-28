<?php namespace DCarbone\PHPFHIR\Enum;

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

/**
 * Class AttributeNameEnum
 * @package DCarbone\PHPFHIR\Enum
 */
class AttributeNameEnum extends AbstractEnum
{
    const NAME         = 'name';
    const VALUE        = 'value';
    const BASE         = 'base';
    const MEMBER_TYPES = 'memberTypes';
    const TYPE         = 'type';
    const _USE         = 'use';
    const MIN_OCCURS   = 'minOccurs';
    const MAX_OCCURS   = 'maxOccurs';
    const REF          = 'ref';
    const FIXED        = 'fixed';
    const MIXED        = 'mixed';
    const _NAMESPACE   = 'namespace';
}