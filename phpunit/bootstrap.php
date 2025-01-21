<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
const PHPFHIR_TEST_CONFIG_FILE = PHPFHIR_TEST_CONFIG_ROOT_DIR . '/config.php';
const PHPFHIR_TEST_COMPOSER_AUTOLOADER_PATH = PHPFHIR_TEST_CONFIG_ROOT_DIR . '/../vendor/autoload.php';
const PHPFHIR_TETS_GENERATED_AUTOLOADER_PATH = PHPFHIR_TEST_CONFIG_ROOT_DIR . '/../output/src/DCarbone/PHPFHIRGenerated/Autoloader.php';

// require generator autoloader
(function () {
    $composer_autoloader = realpath(PHPFHIR_TEST_COMPOSER_AUTOLOADER_PATH);
    echo "Requiring composer autoloader: {$composer_autoloader}\n";
    require $composer_autoloader;
})();

// generate code for test target
(function () {
    $phpfhir_test_target = getenv('PHPFHIR_TEST_TARGET');
    echo "Generating code for target: {$phpfhir_test_target}\n";
    $config = \DCarbone\PHPFHIR\Config::fromArray(require PHPFHIR_TEST_CONFIG_FILE);
    $builder = new DCarbone\PHPFHIR\Builder($config);
    $builder->render();
})();

// require generated autoloader
(function () {
    $generated_autoloader = realpath(PHPFHIR_TETS_GENERATED_AUTOLOADER_PATH);
    echo "Requiring generated autoloader: {$generated_autoloader}\n";
    require $generated_autoloader;
})();

// bootstrap complete
