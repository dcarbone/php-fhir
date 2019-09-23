# php-fhir
Tools for creating PHP classes from the HL7 FHIR Specification

# Installation

This library requires the use of [Composer](https://getcomposer.org/)

# Quick start

A convenient download and generation script is included in this repository. 
The script will download current major versions of FHIR into the `input` folder and 
generate classes for every version in the `output` folder.

* Run `composer install`
* Run `php ./bin/generate.php`

```php
php ./bin/generate.php 
Downloading DSTU1 from https://hl7.org/fhir/DSTU1/fhir-all-xsd.zip
Generating DSTU1 into /Users/pim/web/forks/php-fhir/output
Downloading DSTU2 from https://hl7.org/fhir/DSTU2/fhir-all-xsd.zip
Generating DSTU2 into /Users/pim/web/forks/php-fhir/output
Downloading STU3 from https://hl7.org/fhir/STU3/fhir-all-xsd.zip
Generating STU3 into /Users/pim/web/forks/php-fhir/output
Downloading R4 from https://hl7.org/fhir/R4/fhir-all-xsd.zip
Generating R4 into /Users/pim/web/forks/php-fhir/output
Downloading Build from http://build.fhir.org/fhir-all-xsd.zip
Generating Build into /Users/pim/web/forks/php-fhir/output
Done
```

# Manual Class Generation

## Include library in composer.json

Require entry:
```json
    "dcarbone/php-fhir": "^0.5"
```

## Basic Workflow

The first step is to determine the version of the FHIR spec your implementation supports.  Once done, download
the appropriate class definition XSDs from [http://hl7.org/fhir/directory.html](http://hl7.org/fhir/directory.html).

Uncompress the XSD's and place them in a directory that is readable by PHP's runtime user.

Next comes the fun:

## Class Generation

The class generator utility included with this library is designed to parse the XSD's provided by the FHIR
group into PHP classes, complete with markup and type hinting.

There are 2 important things to note with this section:

1. Your exact implementation will probably vary, don't hesitate to ask if you need some help
2. The class generation should be run ONCE per FHIR version.  Once the classes
have been generated they should only ever be re-generated if your server switches to a new FHIR spec

### Example:

```php
require __DIR__.'/vendor/autoload.php';

$xsdPath = 'path to wherever you un-zipped the xsd files';

$config = new \DCarbone\PHPFHIR\ClassGenerator\Config(['xsdPath' => $xsdPath]);
$generator = new \DCarbone\PHPFHIR\ClassGenerator\Generator($config);

$generator->generate();
```

Using the above code will generate class files under the included [output](./output) directory, under the namespace
` PHPFHIRGenerated `

If you wish the files to be placed under a different directory, pass the path in as the 2nd argument in the
generator constructor.

If you wish the classes to have a different base namespace, pass the desired NS name in as the 3rd argument in the
generator constructor.

## Data Querying

There are a plethora of good HTTP clients you can use to get data out of a FHIR server, so I leave that up to you.

## Response Parsing

As part of the class generation above, a response parsing class called `PHPFHIRResponseParser` will be created
and added into the root namespace directory.  It currently supports JSON and XML response types.

The parser class takes a single optional boolean argument that will determine if it should
attempt to load up the generated Autoloader class.  By default it will do so, but you are free to configure your
own autoloader and not use the generated one if you wish.

### Example:

```php

require 'path to PHPFHIRResponseParser.php';

$parser = new \\YourConfiguredNamespace\\PHPFHIRResponseParser;

$object = $parser->parse($yourResponseData);

```

## JSON Serialization

```php
$json = json_encode($object);
```

## XML Serialization

```php
// To get an XML string back...
$xml = $object->xmlSerialize();

// To get back an instance of \SimpleXMLElement...
$sxe = $object->xmlSerialize(true);
```

XML Serialization utilizes [SimpleXMLElement](http://php.net/manual/en/class.simplexmlelement.php).

## Custom XML Serialization
In some cases, vendors may deviate from the FHIR spec and require some properties of a class to be
serialized as xml attributes instead of a child. The generator supports this through the following configuration.

```php
require __DIR__.'/vendor/autoload.php';

$xsdPath = 'path to wherever you un-zipped the xsd files';

$config = new \DCarbone\PHPFHIR\ClassGenerator\Config([
    'xsdPath' => $xsdPath,
    'xmlSerializationAttributeOverrides' => ['SomeFHIRModel' => 'somePropertyName']
);

$generator = new \DCarbone\PHPFHIR\ClassGenerator\Generator($config);

$generator->generate();

```

See the integration tests for a working example.

## Testing

Currently this library is being tested against v0.0.82, v1.0.2, and v1.8.0 using the open server available here:
http://fhir2.healthintersections.com.au/open/ .

## TODO

- Implement event or pull-based XML parsing for large responses
- Typecast scalar values in XML responses to proper PHP type
- Attempt to implement Date values into PHP DateTime objects
- Tests...

## Suggestions and help

If you have some suggestions for how this lib could be made more useful, more applicable, easier to use, etc, please
let me know.
