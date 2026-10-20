<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Names of the generated validation rules.  The rule classes' "NAME" constants are rendered from these, and the rule
 * maps of generated types and profiles are keyed by them.
 */
enum ValidationRuleNameEnum: string
{
    case MIN_OCCURS = 'min_occurs';
    case MAX_OCCURS = 'max_occurs';
    case VALUE_MIN_LENGTH = 'value_min_length';
    case VALUE_MAX_LENGTH = 'value_max_length';
    case VALUE_ONE_OF = 'value_one_of';
    case VALUE_PATTERN_MATCH = 'value_pattern_match';
    case FIXED_VALUE = 'fixed_value';
    case PATTERN_VALUE = 'pattern_value';
    case CODE_BINDING = 'code_binding';
    case CHOICE_OCCURS = 'choice_occurs';
    case SLICE_OCCURS = 'slice_occurs';
}
