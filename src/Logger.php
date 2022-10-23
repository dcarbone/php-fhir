<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

// if the user is running php version 8.0 or greater, the psr/log interface implementation
// has a stricter definition, and we must be able to support that while maintaining backwards
// compatibility
if (80000 <= PHP_VERSION_ID) {
    require __DIR__ . '/Logger/Logger80.php';
} else {
    require __DIR__ . '/Logger/Logger74.php';
}
