<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles\Rules;

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
 * Everything the generated class for one profile validates: its element rules, slices and invariants, along with
 * the constraints that could not be expressed as rules.
 */
class ProfileRules
{
    /** @var ProfileElementRule[] */
    private readonly array $_elementRules;
    /** @var ProfileSliceRule[] */
    private readonly array $_sliceRules;
    /** @var ProfileInvariant[] */
    private readonly array $_invariants;
    /** @var string[] */
    private readonly array $_skipped;

    /**
     * @param ProfileElementRule[] $elementRules
     * @param ProfileSliceRule[] $sliceRules In definition order; slice accessors are matched to them by index.
     * @param ProfileInvariant[] $invariants
     * @param string[] $skipped Constraints that could not be turned into rules, and why.
     */
    public function __construct(
        array $elementRules = [],
        array $sliceRules = [],
        array $invariants = [],
        array $skipped = [],
    ) {
        $this->_elementRules = array_values($elementRules);
        $this->_sliceRules = array_values($sliceRules);
        $this->_invariants = array_values($invariants);
        $this->_skipped = array_values($skipped);
    }

    /** @return ProfileElementRule[] */
    public function getElementRules(): array
    {
        return $this->_elementRules;
    }

    /** @return ProfileSliceRule[] */
    public function getSliceRules(): array
    {
        return $this->_sliceRules;
    }

    /** @return ProfileInvariant[] */
    public function getInvariants(): array
    {
        return $this->_invariants;
    }

    /** @return string[] */
    public function getSkipped(): array
    {
        return $this->_skipped;
    }
}
