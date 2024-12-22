<?php declare(strict_types=1);

/**
 * PHPUnit bootstrap script
 *
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

const COMPOSER_AUTOLOADER_PATH = __DIR__ . '/../vendor/autoload.php';
const GENERATED_AUTOLOADER_PATH = __DIR__ . '/../output/DCarbone/PHPFHIRGenerated/Autoloader.php';

$composerAutoloader = realpath(COMPOSER_AUTOLOADER_PATH);
$generatedAutoloader = realpath(GENERATED_AUTOLOADER_PATH);

if (!$composerAutoloader) {
    throw new \RuntimeException(sprintf('Copmoser autoloader class file not found at expected path: %s', COMPOSER_AUTOLOADER_PATH));
}
if (!$generatedAutoloader) {
    throw new \RuntimeException(sprintf('Generated autoloader class file not found at expected path: %s', GENERATED_AUTOLOADER_PATH));
}

echo "Requiring composer autoloader: {$composerAutoloader}\n";
require $composerAutoloader;
echo "Requiring generated autoloader: {$generatedAutoloader}\n";
require $generatedAutoloader;
