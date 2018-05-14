<?php
/**
 * Download and generation script for all major FHIR versions
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
 * Copyright 2017-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

// --- autoload setup

date_default_timezone_set('UTC');
require __DIR__.'/../vendor/autoload.php';

// --- use statements

use DCarbone\PHPFHIR\ClassGenerator\Config;
use DCarbone\PHPFHIR\ClassGenerator\Generator;

// ----- constants

define('PHPFHIR_GENERATE_CONFIG_FILE', 'PHPFHIR_GENERATE_CONFIG_FILE');

// ----- cli and config opts

$printHelp = false;
$forceDelete = false;
$configEnv = getenv(PHPFHIR_GENERATE_CONFIG_FILE);
$configArg = '';
$configDef = __DIR__.DIRECTORY_SEPARATOR.'config.php';
$configFile = null;
$schemaPath = '';
$classesPath = '';
$versionsToGenerate = null;

// ----- functions

function missingConfigText($return) {
    global $configEnv, $configArg, $configDef;
    $out = 'Unable to locate generate script configuration file.  I looked in the following locations:'.PHP_EOL;
    $out .= sprintf(
        '   - env var "%s": %s%s',
        PHPFHIR_GENERATE_CONFIG_FILE,
        (false === $configEnv ? 'Not Defined' : $configEnv),
        PHP_EOL
    );
    $out .= sprintf('   - "--config" flag: %s%s', ('' === $configArg ? 'Not Defined' : $configArg), PHP_EOL);
    $out .= sprintf('   - Default: %s%s', $configDef, PHP_EOL);
    $out .= PHP_EOL;
    $out .= 'Please do one of the following:'.PHP_EOL;
    $out .= sprintf('   - Define "%s" environment variable%s', PHPFHIR_GENERATE_CONFIG_FILE, PHP_EOL);
    $out .= '   - Pass "--config" flag with valid path to config file'.PHP_EOL;
    $out .= sprintf('   - Place "config.php" file in "%s"%s', $configDef, PHP_EOL);

    $out .= <<<STRING

Below is an example config file:

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

    if ($return) {
        return $out;
    }

    echo $out;
    exit(1);
}


function exitWithHelp() {
    global $configDef;
    $envvar = PHPFHIR_GENERATE_CONFIG_FILE;
    $out = <<<STRING

PHP-FHIR: Tools for creating PHP classes from the HL7 FHIR Specification

- Links: 
    Source:         https://github.com/dcarbone/php-fhir
    Generated:      https://github.com/dcarbone/php-fhir-generated
    FHIR:           http://hl7.org/fhir

- Flags:
    --help:         Print this help text 
                        ex: ./bin/generate.sh --help
    --force:        Forcibly delete all pre-existing FHIR schema files and output files without being prompted [default: false]
                        ex: ./bin/generate.sh --force
    --config:       Specify location of config [default: {$configDef}]
                        ex: ./bin/generate.sh --config path/to/file
    --versions:     Comma-separated list of specific versions to parse from config
                        ex: ./bin/generate.sh --versions DSTU1,DSTU2

- Configuration:
    There are 3 possible ways to define a configuration file for this script to use:
        1. Define env var {$envvar}
        2. Pass "--config" flag at run time
        3. Place "config.php" in dir {$configDef}


STRING;

    echo $out;
    exit(0);
}

/**
 * TODO: Figure out what to do with Windows...
 *
 * @param string $q
 *
 * @return bool
 */
function yesno($q) {
    global $ins, $null;
    echo "{$q} [enter \"yes\" or \"no\"]: ";
    while (0 !== stream_select($ins, $null, $null, null)) {
        foreach ($ins as $in) {
            $resp = stream_get_line($in, 25, "\n");
            if (is_string($resp)) {
                return substr(strtolower($resp), 0, 1) === 'y';
            }
            return false;
        }
    }
    // some kind of error checking?
    return false;
}

/**
 * @param string $dir
 */
function removeDir($dir) {
    echo "Executing \"rm -rf {$dir}\" ...\n";
    shell_exec('rm -rf '.$dir);
    sleep(1);
    if (file_exists($dir)) {
        echo "Unable to delete dir {$dir}\n";
        exit(1);
    }
    echo "Done.\n";
}


// ----- parameter parsing

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        switch ($arg) {
            case '--help':
                $printHelp = true;
                break;

            case '--force':
                $forceDelete = true;
                break;

            case '--config':
                $configArg = trim($argv[++$i]);
                break;

            case '--versions':
                $versionsToGenerate = array_map('trim', explode(',', $argv[++$i]));
                break;
        }
    }
}

// try to determine which config file to use...
if ('' !== $configArg) {
    $configFile = $configArg;
} else if (false !== $configEnv) {
    $configFile = $configEnv;
} else {
    $configFile = $configDef;
}

if ($printHelp) {
    exitWithHelp(); // calls exit(0); at end
}

if (!file_exists($configFile)) {
    missingConfigText(false);
}

if (!is_readable($configFile)) {
    echo "Specified config file \"{$configFile}\" is not readable by this process, please check permissions and try again\n";
    exit(1);
}

$config = require $configFile;

$schemaPath = (isset($config['schemaPath']) ? $config['schemaPath'] : null);
$classesPath = (isset($config['classesPath']) ? $config['classesPath'] : null);
$versions = (isset($config['versions']) ? $config['versions'] : null);

if (null === $schemaPath) {
    echo "Config file \"{$configFile}\" is missing \"schemaPath\" directive\n";
    exit(1);
}
if (!is_dir($schemaPath) || !is_readable($schemaPath) || !is_writable($schemaPath)) {
    echo "Specified schema path \"{$schemaPath}\" either does not exist, is not readable, or is not writable.\n";
    exit(1);
}
if (null === $classesPath) {
    echo "Config file \"{$configFile}\" is missing \"classesPath\" directive\n";
    exit(1);
}
if (!is_dir($classesPath) || !is_readable($classesPath) || !is_writable($classesPath)) {
    echo "Specified classes path \"{$classesPath}\" either does not exist, is not readable, or is not writable.\n";
    exit(1);
}

if (!is_array($versions)) {
    echo "Config file \{$configFile}\" is either missing \"versions\" directive or has it set to something other than an associative array\n";
    exit(1);
}

$schemaPath = realpath($schemaPath);
$classesPath = realpath($classesPath);

$ins = [STDIN];
$null = null;

$existsMsg = ' already exists, please remove it before running generator';

$dir = $classesPath.DIRECTORY_SEPARATOR.'HL7';
if (is_dir($dir)) {
    if ($forceDelete || yesno("Directory \"{$dir}\" already exists, ok to delete?")) {
        removeDir($dir);
    } else {
        echo "Exiting\n";
        exit(0);
    }
}

if (null === $versionsToGenerate) {
    $versionsToGenerate = array_keys($versions);
}

echo sprintf(
    "\nGenerating classes for versions: %s\n\n",
    implode(', ', $versionsToGenerate)
);

foreach ($versionsToGenerate as $version) {
    if (!isset($versions[$version])) {
        echo sprintf(
            "Version \"%s\" not found in config.  Available: %s\n\n",
            $version,
            implode(', ', array_keys($versions))
        );
        exit(1);
    }

    $versionConf = $versions[$version];

    $url = $versionConf['url'];
    $namespace = $versionConf['namespace'];
    $version = trim($version);

    // Download zip files
    echo 'Downloading '.$version.' from '.$url.PHP_EOL;
    $zipFileName = $schemaPath.DIRECTORY_SEPARATOR.$version.'.zip';

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

    // Download/extract ZIP file
    copy($url, $zipFileName);
    $zip = new ZipArchive;

    $schemaDir = $schemaPath.DIRECTORY_SEPARATOR.$version;
    $res = $zip->open($schemaDir.'.zip');

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

    echo sprintf(
        'Generating "%s" into %s%s%s',
        $version,
        $classesPath,
        str_replace('\\', DIRECTORY_SEPARATOR, $namespace),
        PHP_EOL
    );
    $config = new Config([
        'xsdPath'         => $schemaDir,
        'outputPath'      => $classesPath,
        'outputNamespace' => $namespace,
    ]);

    $generator = new Generator($config);
    $generator->generate();
}

echo PHP_EOL.'Generation completed'.PHP_EOL;