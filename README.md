# php-fhir
Tools for creating PHP classes from the HL7 FHIR Specification

# Installation

This library requires the use of [Composer](https://getcomposer.org/)

Require entry:
```json
    "dcarbone/php-fhir": "0.4.*"
```

# Basic Workflow

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

$generator = new \DCarbone\PHPFHIR\ClassGenerator\Generator($xsdPath);

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

### PHP 5.3.x example:

```php
$json = json_encode($object->jsonSerialize());
```

### PHP \>= 5.4.0

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

## Testing

Currently this library is being tested against v0.0.82 and v1.0.2. using the open server available here:
http://fhir2.healthintersections.com.au/open/ .

## TODO

- Implement event or pull-based XML parsing for large responses
- Typecast scalar values in XML responses to proper PHP type
- Attempt to implement Date values into PHP DateTime objects
- Tests...

## Suggestions and help

If you have some suggestions for how this lib could be made more useful, more applicable, easier to use, etc, please
let me know.
