<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

enum VersionKeys: string
{
    case SOURCE_URL = 'sourceUrl';
    case NAMESPACE = 'namespace';
    case TEST_ENDPOINT = 'testEndpoint';
    case DEFAULT_CONFIG = 'defaultConfig';

    /**
     * @return \DCarbone\PHPFHIR\VersionKeys[]
     */
    public static function required(): array
    {
        return [
            self::NAMESPACE,
            self::SOURCE_URL,
        ];
    }

    /**
     * @return \DCarbone\PHPFHIR\VersionKeys[]
     */
    public static function optional(): array
    {
        $required = self::required();
        $out = [];
        foreach (self::cases() as $case) {
            if (!in_array($case, $required, true)) {
                $out[] = $case;
            }
        }
        return $out;
    }
}