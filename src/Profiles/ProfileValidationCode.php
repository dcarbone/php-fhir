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
 * PHP source generated from a profile's rules, to be placed in the profile class.
 */
final class ProfileValidationCode
{
    public readonly string $body;
    public readonly string $members;
    public readonly array $sliceMatchers;
    public readonly array $skipped;
    public readonly array $fieldRules;

    /**
     * @param string $body Statements making up the body of _getValidationErrors(), appending to $errs.
     * @param string $members Constants and private static methods the body relies on.
     * @param array<int, array{method: string, class: string}> $sliceMatchers By slice index, the private static method
     *        that tells whether an item belongs to the slice, and the imported name of the item's class.
     * @param string[] $skipped Rules that could not be turned into code, and why.
     * @param array<string, array<string, int>> $fieldRules Maximum cardinality of top-level collections, as max_occurs rules
     *        for the type's own validation.
     */
    public function __construct(
        string $body = '',
        string $members = '',
        array $sliceMatchers = [],
        array $skipped = [],
        array $fieldRules = [],
    ) {
        $this->body = $body;
        $this->members = $members;
        $this->sliceMatchers = $sliceMatchers;
        $this->skipped = $skipped;
        $this->fieldRules = $fieldRules;
    }
}
