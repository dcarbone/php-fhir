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
 * One source code of a ConceptMap group.  Only the targets it is declared equivalent to are retained: any other
 * relationship does not make the target a usable replacement for the source code.
 */
final class ProfileConceptMapElement
{
    /**
     * @param list<string> $equivalentTargets Distinct target codes declared "equivalent".
     */
    public function __construct(
        public readonly string $code,
        public readonly array $equivalentTargets,
    ) {
    }
}
