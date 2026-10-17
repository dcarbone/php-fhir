<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

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
 * Rules and supporting constants generated from a profile's rules, to be placed in the profile class.
 */
final class ProfileValidationCode
{
    public readonly string $constants;
    public readonly array $rules;
    public readonly array $sliceMatchers;
    public readonly array $skipped;

    /**
     * @param string $constants Class constants the rules rely on.
     * @param array<string, array<string, mixed>> $rules By rule map key, written as a PHP expression such as
     *        "self::FIELD_NAME" or "'contact.relationship'", the rules of the validation system and their constraints.
     * @param array<int, array{constant: string, class: string}> $sliceMatchers By slice index, the constant holding
     *        the slice's match pattern, and the imported name of the class of the items it matches.
     * @param string[] $skipped Rules that could not be generated, and why.
     */
    public function __construct(
        string $constants = '',
        array $rules = [],
        array $sliceMatchers = [],
        array $skipped = [],
    ) {
        $this->constants = $constants;
        $this->rules = $rules;
        $this->sliceMatchers = $sliceMatchers;
        $this->skipped = $skipped;
    }
}
