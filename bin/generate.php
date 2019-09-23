<?php
/**
 * Download and generation script for all major FHIR versions
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
 * Copyright 2017 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\ClassGenerator\Config;
use DCarbone\PHPFHIR\ClassGenerator\Generator;

date_default_timezone_set('UTC');
require __DIR__ . '/../vendor/autoload.php';

/**
 *
 */
function exitWithHelp()
{
    $dir = __DIR__;
    echo <<<STRING
Please create and populate  a "config.php" file in {$dir}. Here is a template:

<?php
return [
    'schemaPath'  => __DIR__ . '/../input',
    'classesPath' => __DIR__ . '/../output',
    'versions' => [
        'DSTU1'  => ['url' => 'http://hl7.org/fhir/DSTU1/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\DSTU1'],
        'DSTU2'  => ['url' => 'http://hl7.org/fhir/DSTU2/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\DSTU2'],
        'STU3'   => ['url' => 'http://hl7.org/fhir/STU3/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\STU3'],
        'Build'  => ['url' => 'http://build.fhir.org/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\Build']
    ]
];

STRING;
    exit(1);
}

/**
 * @param $q
 *
 * @return bool
 */
function yesno($q)
{
    global $ins, $null;
    echo "{$q} [enter \"yes\" or \"no\"]: ";
    while (0 !== stream_select($ins, $null, $null, null)) {
        foreach ($ins as $in) {
            $resp = stream_get_line($in, 1, "\n");
            if (is_string($resp)) {
                $l = strtolower($resp);

                return $l === 'y';
            }

            $ins = [];

            return false;
        }
    }

    // some kind of error checking?
    return false;
}

/**
 * @param $dir
 */
function removeDir($dir)
{
    echo "Executing \"rm -rf {$dir}\" ...\n";
    shell_exec('rm -rf ' . $dir);
    sleep(1);
    if (file_exists($dir)) {
        echo "Unable to delete dir {$dir}\n";
        exit(1);
    }
    echo "Done.\n";
}

function downloadFile($url, $fileName) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSLVERSION,3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    file_put_contents($fileName, $result);
}

if (!file_exists(__DIR__ . '/config.php')) {
    exitWithHelp();
}

$config = require __DIR__ . '/config.php';

if (!isset($config['schemaPath']) || !isset($config['classesPath']) || !isset($config['versions'])) {
    exitWithHelp();
}

$forceDelete = false;
if ($argc > 1) {
    foreach ($argv as $offset => $arg) {
        if ($offset > 0) {
            switch ($arg) {
                case '--force':
                    $forceDelete = true;
                    break;
            }
        }
    }
}

$schemaPath = realpath(trim($config['schemaPath']));
$classesPath = realpath(trim($config['classesPath']));
$versions = $config['versions'];

$ins = [STDIN];
$null = null;

$existsMsg = ' already exists, please remove it before running generator';

$dir = $classesPath . DIRECTORY_SEPARATOR . 'HL7';
if (is_dir($dir)) {
    if ($forceDelete || yesno("Directory \"{$dir}\" already exists, ok to delete?")) {
        removeDir($dir);
    } else {
        echo "Exiting\n";
        exit(0);
    }
}

foreach ($versions as $name => $version) {

    $url = $version['url'];
    $namespace = $version['namespace'];
    $name = trim($name);

    // Download zip files
    echo 'Downloading ' . $name . ' from ' . $url . PHP_EOL;
    $zipFileName = $schemaPath . DIRECTORY_SEPARATOR . $name . '.zip';

    if (file_exists($zipFileName)) {
        if ($forceDelete || yesno("ZIP \"{$zipFileName}\" already exists, ok to delete?")) {
            echo "Deleting {$zipFileName} ...\n";
            unlink($zipFileName);
            if (file_exists($zipFileName)) {
                echo "Unable to delete file {$zipFileName}\n";
                exit(1);
            }
            echo "Deleted.\n";
        } else {
            echo "Exiting\n";
            exit(0);
        }
    }

    downloadFile($url, $zipFileName);

    // Download/extract ZIP file
    $zip = new ZipArchive;

    $schemaDir = $schemaPath . DIRECTORY_SEPARATOR . $name;
    $res = $zip->open($schemaDir . '.zip');

    if (is_dir($schemaDir)) {
        if ($forceDelete || yesno("Schema dir \"{$schemaDir}\" already exists, ok to delete?")) {
            removeDir($schemaDir);
        } else {
            echo "Exiting\n";
            exit(0);
        }
    }

    if (!mkdir($schemaDir, 0777, true)) {
        echo "Unable to create directory \"{$schemaDir}\. Exiting\n";
        exit(1);
    }

    // Extract Zip
    $zip->extractTo($schemaDir);
    $zip->close();

    echo 'Generating ' . $name . ' into ' . $classesPath . PHP_EOL;
    $config = new Config([
      'xsdPath'         => $schemaDir,
      'outputPath'      => $classesPath,
      'outputNamespace' => $namespace,
    ]);

    $generator = new Generator($config);
    $generator->generate();
}

echo 'Done' . PHP_EOL . PHP_EOL;