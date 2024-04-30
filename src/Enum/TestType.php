<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Enum;

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

enum TestType: string
{
    use ValuesTrait;

    case BASE= 'base';
    case UNIT = 'unit';
    case INTEGRATION = 'integration';
    case VALIDATION = 'validation';

    public function namespaceSlug(): string
    {
        return match ($this) {
            self::BASE => PHPFHIR_TESTS_NAMESPACE_BASE,
            self::UNIT => PHPFHIR_TESTS_NAMESPACE_UNIT,
            self::INTEGRATION => PHPFHIR_TESTS_NAMESPACE_INTEGRATION,
            self::VALIDATION => PHPFHIR_TESTS_NAMESPACE_VALIDATION,
        };
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestType|string ...$other
     * @return bool
     */
    public function isOneOf(TestType|string ...$other): bool
    {
        $vals = self::values();
        foreach ($other as $name) {
            if ($this === $name || in_array($name, $vals, true)) {
                return true;
            }
        }

        return false;
    }
}