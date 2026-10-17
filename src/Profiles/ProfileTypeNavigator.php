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
 * Describes the shape of the generated FHIR types of one version, so profile validation code can be generated against
 * their real getters.
 */
interface ProfileTypeNavigator
{
    /**
     * @param string $typeKey FHIR name of a type, or the key of a property's type.
     * @param string $field FHIR name of the field.
     * @return null|ProfileProperty Null if the type has no such field, or it cannot be validated.
     */
    public function getProperty(string $typeKey, string $field): null|ProfileProperty;

    /**
     * Every concrete field of a choice element, e.g. "deceased" yields deceasedBoolean and deceasedDateTime.
     *
     * @return ProfileProperty[]
     */
    public function getChoiceProperties(string $typeKey, string $base): array;
}
