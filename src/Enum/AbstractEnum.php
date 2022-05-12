<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

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

use MyCLabs\Enum\Enum;

/**
 * Class AbstractEnum
 * @package DCarbone\PHPFHIR\Enum
 */
abstract class AbstractEnum extends Enum
{
    /**
     * @param mixed $enumValue
     * @return bool
     */
    public function is($enumValue): bool
    {
        if (is_scalar($enumValue)) {
            return $enumValue === $this->getValue();
        }
        return $this->equals($enumValue);
    }

    /**
     * @param array $enumValues
     * @return bool
     */
    public function isOneOf(array $enumValues): bool
    {
        foreach ($enumValues as $kind) {
            if ($this->is($kind)) {
                return true;
            }
        }
        return false;
    }
}