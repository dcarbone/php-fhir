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

use DCarbone\PHPFHIR\Profiles\Rules\ProfileSliceRule;

/**
 * A typed accessor generated for a slice of a profile's top-level element, e.g. getSsnIdentifier() for an "ssn"
 * slice of "identifier".
 */
class ProfileSliceAccessor
{
    private readonly ProfileSliceMatcher $matcher;
    private readonly string $method;
    private readonly string $getter;
    private readonly bool $single;

    /**
     * @param string $method Name of the generated accessor method.
     * @param string $getter Name of the getter of the sliced element the accessor reads from.
     * @param bool $single Whether the accessor returns at most one item rather than a list.
     */
    public function __construct(ProfileSliceMatcher $matcher, string $method, string $getter, bool $single)
    {
        $this->matcher = $matcher;
        $this->method = $method;
        $this->getter = $getter;
        $this->single = $single;
    }

    public function getMatcher(): ProfileSliceMatcher
    {
        return $this->matcher;
    }

    public function getSlice(): ProfileSliceRule
    {
        return $this->matcher->getSlice();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getGetter(): string
    {
        return $this->getter;
    }

    public function isSingle(): bool
    {
        return $this->single;
    }
}
