<?php
/**
 * Download and generation script for all major FHIR versions
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
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
use DCarbone\PHPFHIR\ClassGenerator\Generator;

date_default_timezone_set('UTC');
require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__.'/config.php';
$schemaPath = realpath(trim($config['schemaPath']));
$classesPath = realpath(trim($config['classesPath']));
$versions = $config['versions'];

// Empty output root directory
exec('rm -rf ' . $classesPath . DIRECTORY_SEPARATOR. 'HL7');

foreach ($versions as $name => $version) {

    $url = $version['url'];
    $namespace = $version['namespace'];
    $name = trim($name);

    // Download zip files
    echo 'Downloading ' . $name . ' from ' . $url . PHP_EOL;
    copy($url, $schemaPath . DIRECTORY_SEPARATOR . $name . '.zip');

    // Download/extract ZIP file
    $zip = new ZipArchive;

    $schemaDir = $schemaPath . DIRECTORY_SEPARATOR . $name;
    $res = $zip->open($schemaDir . '.zip');

    if (is_dir($schemaDir)) {
        exec('rm -rf ' . $schemaDir);
    }

    mkdir($schemaDir, 0777, true);

    // Extract Zip
    $zip->extractTo($schemaDir);
    $zip->close();

    echo 'Generating ' . $name .' into '.$schemaDir.PHP_EOL;
    $generator = new Generator($schemaDir, $classesPath, $namespace);
    $generator->generate();
}

echo 'Done' . PHP_EOL . PHP_EOL;
