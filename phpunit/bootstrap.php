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

const PHPFHIR_TEST_CONFIG_ROOT_DIR = __DIR__;
const PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR = PHPFHIR_TEST_CONFIG_ROOT_DIR . '/../output/tests/resources';

echo 'Creating test resource download dir: ' . PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR . PHP_EOL;

if (!is_dir(PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR) && !mkdir(PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR, 0755, true)) {
    throw new \RuntimeException(sprintf('Failed to create test resource download directory: %s', PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR));
}

# define env to be used in tests
putenv('PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR='.PHPFHIR_TEST_RESOURCE_DOWNLOAD_DIR);

const PHPFHIR_TEST_COMPOSER_AUTOLOADER_PATH = PHPFHIR_TEST_CONFIG_ROOT_DIR . '/../vendor/autoload.php';
const PHPFHIR_TETS_GENERATED_AUTOLOADER_PATH = PHPFHIR_TEST_CONFIG_ROOT_DIR . '/../output/DCarbone/PHPFHIRGenerated/Autoloader.php';

$composerAutoloader = realpath(PHPFHIR_TEST_COMPOSER_AUTOLOADER_PATH);
$generatedAutoloader = realpath(PHPFHIR_TETS_GENERATED_AUTOLOADER_PATH);

if (!$composerAutoloader) {
    throw new \RuntimeException(sprintf('Copmoser autoloader class file not found at expected path: %s', PHPFHIR_TEST_COMPOSER_AUTOLOADER_PATH));
}
if (!$generatedAutoloader) {
    throw new \RuntimeException(sprintf('Generated autoloader class file not found at expected path: %s', PHPFHIR_TETS_GENERATED_AUTOLOADER_PATH));
}

echo "Requiring composer autoloader: {$composerAutoloader}\n";
require $composerAutoloader;
echo "Requiring generated autoloader: {$generatedAutoloader}\n";
require $generatedAutoloader;

unset($composerAutoloader, $generatedAutoloader);
