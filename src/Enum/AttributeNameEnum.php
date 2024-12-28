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

enum AttributeNameEnum : string
{
    case NAME         = 'name';
    case VALUE        = 'value';
    case BASE         = 'base';
    case MEMBER_TYPES = 'memberTypes';
    case TYPE         = 'type';
    case USE          = 'use';
    case MIN_OCCURS   = 'minOccurs';
    case MAX_OCCURS   = 'maxOccurs';
    case REF          = 'ref';
    case FIXED        = 'fixed';
    case MIXED        = 'mixed';
    case NAMESPACE    = 'namespace';

    /**
     * @param \DCarbone\PHPFHIR\Enum\AttributeNameEnum|string ...$other
     * @return bool
     */
    public function isOneOf(AttributeNameEnum|string ...$other): bool
    {
        return in_array($this, $other, true) || in_array($this->value, $other, true);
    }
}