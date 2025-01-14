# php-fhir
Tools for creating PHP classes from the HL7 FHIR Specification

If you're looking  to use the classes generated by this library, you may want the
[php-fhir-generated](https://github.com/dcarbone/php-fhir-generated) repo instead.

<!-- TOC -->
* [php-fhir](#php-fhir)
* [Install as Standalone Generator](#install-as-standalone-generator)
* [Install as Library](#install-as-library)
* [Version Table](#version-table)
* [Basic Usage](#basic-usage)
  * [Class Generation](#class-generation)
    * [Generation Example](#generation-example)
  * [Data Querying](#data-querying)
  * [Response Parsing](#response-parsing)
  * [Parsing Example](#parsing-example)
* [Serialization](#serialization)
  * [JSON Serialization](#json-serialization)
  * [XML Serialization](#xml-serialization)
* [Testing](#testing)
  * [TODO](#todo)
  * [Suggestions and help](#suggestions-and-help)
<!-- TOC -->

# Install as Standalone Generator
If you wish to use this package as a standalone generator:
 
1. Check out the desired branch or tag
2. Execute `composer install` from root of project directory
3. Execute `./bin/generate.sh`
4. Answer all prompts
   * If no custom configuration file is defined, definitions will be downloaded to `./input` and
classes will be generated under `./output` 
   * You can execute `./bin/generate.sh --help` for details on how to utilize this script
   * You can configure various aspects of this script by altering the values in [./bin/config.php](./bin/config.php)

This script will download configured major versions of FHIR into the `input` folder and
generate classes for every version in the `output` folder.

# Install as Library
If you wish to use the generator as part of a project, you can include it as a composer
dependency:

```shell
composer require dcarbone/php-fhir
```

From there, you can reference the [Example](#generation-example) block for a quick example on how to
configure and execute the generator.

# Version Table

| PHPFHIR Version | PHP Versions | FHIR Versions                    |
|-----------------|--------------|----------------------------------|
| v2              | 5.4-7.4      | DSTU1, DSTU2, STU3, R4 (<v4.3.0) |
| v3              | 8.1+         | DSTU1, DSTU2, STU3, R4, R5       |

# Basic Usage

The first step is to determine the version of the FHIR spec your implementation supports.  Once done, download
the appropriate class definition XSDs from [http://hl7.org/fhir/directory.html](http://hl7.org/fhir/directory.html).

Uncompress the XSD's and place them in a directory that is readable by PHP's runtime user.

Next comes the fun:

## Class Generation

The class generator utility included with this library is designed to parse the XSD's provided by the FHIR
group into PHP classes, complete with markup and type hinting.

There are 2 important things to note with this section:

1. Your exact implementation will probably vary, don't hesitate to ask if you need some help
2. The class generation should be run ONCE per FHIR version.  Once the classes have been generated they should only 
   ever be re-generated if your server switches to a new FHIR spec

### Generation Example

You can view an example config array here: [bin/config.php](./bin/config.php).

```php
// first, build new configuration class
$config = new \DCarbone\PHPFHIR\Config(require 'config.php');

// if you wish to limit the versions generated to a subset of those configured:
// $config->setVersionsToGenerate(['DSTU2', 'STU3']);

// next, iterate through all configured versions and render code:
foreach ($config->getVersionsIterator() as $versionConfig) {
    $versionConfig->getDefinition()->getBuilder()->render();
}
```

## Data Querying

Currently only a very simple client intended for debugging use is generated.  A future goal is to generate a more
fully-featured client.

## Response Parsing

As part of the class generation above, a response parsing class called `PHPFHIRResponseParser` will be created
and added into the root namespace directory.  It currently supports JSON and XML response types.

The parser class takes a single optional boolean argument that will determine if it should
attempt to load up the generated Autoloader class.  By default it will do so, but you are free to configure your
own autoloader and not use the generated one if you wish.

## Parsing Example

```php

require 'path to PHPFHIRResponseParserConfig.php';
require 'path to PHPFHIRResponseParser.php';

// build config
$config = new \YourConfiguredNamespace\PHPFHIRConfig([
    'registerAutoloader' => true, // use if you are not using Composer
    'libxmlOpts' => LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL // choose different libxml arguments if you want, ymmv.
    'rootXmlns' => 'https://hl7.org/fhir', // a specific root xmlns to use, if the source does not return one
    'overrideSourceXmlns' => true, // set this to true if you want the 'rootXmlns' value you defined to override any value seen from source 
]);

// build parser
$parser = new \YourConfiguredNamespace\PHPFHIRResponseParser($config);

// provide input, receive output.
$object = $parser->parse($yourResponseData);

```

# Serialization

## JSON Serialization

```php
$json = json_encode($object);
```

## XML Serialization

```php
// To get an instance of \XMLWriter...
$xw = $object->xmlSerialize(null, $yourConfigInstance);

// to get as XML string...
$xml = $xw->outputMemory(true);

// you can alternatively have the output written directly to a file:
$xw = new \YourConfiguredNamespace\PHPFHIRXmlWriter();
$xw->openUri('file:///some/directory/fhir-resource.xml');
$object->xmlSerialize($xw, $yourConfigInstance);
```

XML Serialization utilizes [XMLWriter](https://www.php.net/manual/en/book.xmlwriter.php).

# Testing

As part of class generation, a directory & namespace called `PHPFHIRTests` is created under the root namespace and
output directory.

## TODO

- Refactor template system to use Twig.
- Improve template loading and iteration system, too squirrely right now.
- Implement serialization abstraction, allowing for more flexible serialization options.
  - XMLReader / XMLParser pull or event parsing
  - Something like [pcrov/JSONReader](https://github.com/pcrov/JsonReader) for larger JSON responses
- Improved client implementation
  - Allow for persisting to, and parsing from, disk.

## Suggestions and help

