<?php declare(strict_types=1);

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

use Rector\Config\RectorConfig;

require_once __DIR__ . '/common.php';

/*
 * Rector configuration for the GENERATED code tree (output/src, output/tests) only.
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/../output/src',
        __DIR__ . '/../output/tests',
    ]);

    _common_rector_config($rectorConfig);

    // never touch the generator itself, only its output.
    $rectorConfig->skip([
        __DIR__ . '/../src',
        __DIR__ . '/../bin',
        __DIR__ . '/../tests',
        __DIR__ . '/../template',
    ]);
};
