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
 * The generated means of recognising members of a slice: the constant holding its match pattern, and the class of
 * the items it matches.
 */
class ProfileSliceMatcher
{
    private readonly ProfileSliceRule $slice;
    private readonly string $constant;
    private readonly string $className;

    /**
     * @param string $constant Name of the class constant holding the slice's match pattern.
     * @param string $className Imported name of the class of the items the slice matches.
     */
    public function __construct(ProfileSliceRule $slice, string $constant, string $className)
    {
        $this->slice = $slice;
        $this->constant = $constant;
        $this->className = $className;
    }

    public function getSlice(): ProfileSliceRule
    {
        return $this->slice;
    }

    public function getConstant(): string
    {
        return $this->constant;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
